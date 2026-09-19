<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\VeeMagPrintLinkMail;
use App\Models\VeeMagIssue;
use App\Models\VeeMagPrintOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Print companion checkout for a VeeMag issue (spec v1.1, sections 29-37).
 *
 * The reader presses Print on an issue page; we open a Stripe checkout for
 * that specific issue at the issue price plus shipping, mail them the same
 * link so they can finish later, and send them straight on to Stripe.
 *
 * Stripe keys live in the `settings` table, not .env - read them through
 * GetpaymentMethod() exactly as PaymentController does.
 */
class VeeMagPrintController extends Controller
{
    /** Currencies Stripe expects in whole units rather than cents. */
    private const ZERO_DECIMAL = ['XAF', 'XOF', 'JPY', 'KRW'];

    /** Start a print order and hand the reader to Stripe. */
    public function checkout(Request $request, string $slug)
    {
        $issue = VeeMagIssue::published()->with('publication')
            ->where('slug', $slug)->firstOrFail();

        if (! $issue->print_enabled) {
            return $this->fail($request, __('frontend.print_unavailable'));
        }

        $secret = GetpaymentMethod('stripe_secretkey');
        if (! $secret) {
            return $this->fail($request, __('frontend.print_unavailable'));
        }

        $user = Auth::user();
        $email = $user->email ?? $request->input('email');
        if (! $email) {
            return $this->fail($request, __('frontend.print_need_email'));
        }

        $currency = strtoupper((string) GetcurrentCurrency() ?: 'USD');
        $price = $issue->print_price_effective;
        $unitAmount = $this->toMinorUnits($price, $currency);

        // The order exists before Stripe does, so an abandoned checkout is
        // still visible to the team.
        $order = VeeMagPrintOrder::create([
            'issue_id' => $issue->id,
            'user_id'  => $user->id ?? null,
            'email'    => $email,
            'amount'   => $price,
            'currency' => $currency,
            'status'   => 'pending',
        ]);

        try {
            $stripe = new \Stripe\StripeClient($secret);

            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'customer_email' => $email,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => trim(($issue->publication->title ?? 'VeeMag') . ' - ' . $issue->title),
                            'description' => trim($issue->issue_label . ' - printed edition'),
                        ],
                        'unit_amount' => $unitAmount,
                    ],
                    'quantity' => 1,
                ]],
                // Shipping is charged on top of the issue price.
                'shipping_address_collection' => [
                    'allowed_countries' => $this->allowedCountries(),
                ],
                'shipping_options' => $this->shippingOptions($currency),
                'metadata' => [
                    'veemag_print_order_id' => (string) $order->id,
                    'veemag_issue_id'       => (string) $issue->id,
                    'veemag_issue_slug'     => $issue->slug,
                ],
                'success_url' => route('veemag.print.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('veemag.detail', $issue->slug),
            ]);
        } catch (\Throwable $e) {
            Log::error('VeeMag print checkout failed', [
                'issue' => $issue->slug,
                'error' => $e->getMessage(),
            ]);
            $order->update(['status' => 'failed']);

            return $this->fail($request, __('frontend.print_failed'));
        }

        $order->update([
            'stripe_session_id' => $session->id,
            'checkout_url'      => $session->url,
        ]);

        // Mail the same link so the reader can finish on another device. The
        // platform has no queue worker, so this is sent inline and is strictly
        // best effort - a mail outage must not block the checkout.
        $this->mailLink($email, $issue, $session->url, $price, $currency);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['redirect' => $session->url]);
        }

        return redirect()->away($session->url);
    }

    /** Return leg from Stripe: reconcile the order. */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        $order = null;

        if ($sessionId && ($secret = GetpaymentMethod('stripe_secretkey'))) {
            try {
                $stripe = new \Stripe\StripeClient($secret);
                $session = $stripe->checkout->sessions->retrieve($sessionId, []);

                $order = VeeMagPrintOrder::where('stripe_session_id', $sessionId)->first();
                if ($order && $session->payment_status === 'paid') {
                    $order->update([
                        'status'   => 'paid',
                        'paid_at'  => now(),
                        'shipping' => json_decode(json_encode(
                            $session->shipping_details ?? $session->customer_details ?? []
                        ), true),
                        // Stripe reports the true total once shipping is chosen.
                        'amount'   => $this->fromMinorUnits(
                            (int) ($session->amount_total ?? 0),
                            strtoupper((string) ($session->currency ?? $order->currency))
                        ),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('VeeMag print reconcile failed', ['error' => $e->getMessage()]);
            }
        }

        return view('frontend::veemag.print_success', [
            'order' => $order,
            'issue' => $order?->issue,
        ]);
    }

    /** Best-effort delivery of the checkout link. */
    private function mailLink(string $email, VeeMagIssue $issue, string $url, float $price, string $currency): void
    {
        // A dev mail stub is worse than no mail: it throws on every order.
        $host = (string) config('mail.mailers.smtp.host');
        if (config('mail.default') === 'smtp' && in_array($host, ['mailhog', 'localhost', '127.0.0.1', ''], true)) {
            Log::info('VeeMag print: mail not configured, skipping link email', ['email' => $email]);

            return;
        }

        try {
            Mail::to($email)->send(new VeeMagPrintLinkMail($issue, $url, $price, $currency));
        } catch (\Throwable $e) {
            Log::warning('VeeMag print: link email failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Shipping choices, charged on top of the $20 issue price.
     *
     * Amounts are settings so they can be tuned without a deploy.
     */
    private function shippingOptions(string $currency): array
    {
        $rates = [
            ['label' => 'Standard shipping', 'setting' => 'veemag_print_ship_standard', 'default' => 6.95, 'min' => 5, 'max' => 10],
            ['label' => 'Express shipping',  'setting' => 'veemag_print_ship_express',  'default' => 19.95, 'min' => 2, 'max' => 4],
        ];

        $options = [];
        foreach ($rates as $rate) {
            $amount = (float) (GetSettingValue($rate['setting']) ?: $rate['default']);
            $options[] = [
                'shipping_rate_data' => [
                    'type' => 'fixed_amount',
                    'fixed_amount' => [
                        'amount'   => $this->toMinorUnits($amount, $currency),
                        'currency' => $currency,
                    ],
                    'display_name' => $rate['label'],
                    'delivery_estimate' => [
                        'minimum' => ['unit' => 'business_day', 'value' => $rate['min']],
                        'maximum' => ['unit' => 'business_day', 'value' => $rate['max']],
                    ],
                ],
            ];
        }

        return $options;
    }

    /** Countries the print edition ships to. */
    private function allowedCountries(): array
    {
        $configured = GetSettingValue('veemag_print_ship_countries');
        if ($configured) {
            $list = array_filter(array_map('trim', explode(',', strtoupper($configured))));
            if ($list) {
                return array_values($list);
            }
        }

        return ['US', 'CA', 'GB', 'IE', 'AU', 'NZ', 'DE', 'FR', 'ES', 'IT', 'NL', 'SE', 'NO', 'DK'];
    }

    private function toMinorUnits(float $amount, string $currency): int
    {
        return in_array($currency, self::ZERO_DECIMAL, true)
            ? (int) round($amount)
            : (int) round($amount * 100);
    }

    private function fromMinorUnits(int $amount, string $currency): float
    {
        return in_array($currency, self::ZERO_DECIMAL, true)
            ? (float) $amount
            : $amount / 100;
    }

    private function fail(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['error' => $message], 422);
        }

        return redirect()->back()->withErrors($message);
    }
}
