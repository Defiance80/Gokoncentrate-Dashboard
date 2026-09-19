<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\VeeMagIssue;
use App\Models\VeeMagPrintOrder;
use Illuminate\Http\Request;

/**
 * Admin surface for the VeeMag print companion.
 *
 * Two jobs: set the platform-wide print pricing, and decide which issues
 * actually offer a printed edition. Without this page `print_enabled`
 * defaults to 0 on every issue and the Print button never appears.
 */
class VeeMagPrintController extends Controller
{
    /** Platform-wide settings this page owns. */
    private array $keys = [
        'veemag_print_price',          // default price per copy, excl. shipping
        'veemag_print_ship_standard',
        'veemag_print_ship_express',
        'veemag_print_ship_countries', // comma-separated ISO codes
    ];

    public function index()
    {
        $settings = [];
        foreach ($this->keys as $key) {
            $settings[$key] = Setting::where('name', $key)->value('val');
        }

        // Defaults mirror the controller's fallbacks so the form is never blank.
        $settings['veemag_print_price'] = $settings['veemag_print_price'] ?: '20.00';
        $settings['veemag_print_ship_standard'] = $settings['veemag_print_ship_standard'] ?: '6.95';
        $settings['veemag_print_ship_express'] = $settings['veemag_print_ship_express'] ?: '19.95';

        $issues = VeeMagIssue::with('publication')
            ->published()
            ->orderByDesc('release_date')
            ->orderByDesc('id')
            ->get();

        $orders = VeeMagPrintOrder::with('issue')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('backend.veemag-print.index', compact('settings', 'issues', 'orders'));
    }

    /** Save the platform-wide pricing. */
    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'veemag_print_price'          => ['required', 'numeric', 'min:0'],
            'veemag_print_ship_standard'  => ['required', 'numeric', 'min:0'],
            'veemag_print_ship_express'   => ['required', 'numeric', 'min:0'],
            'veemag_print_ship_countries' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($this->keys as $key) {
            Setting::add($key, (string) ($data[$key] ?? ''), 'string', 'misc');
        }

        return redirect()->route('backend.veemag-print.index')
            ->with('status', __('messages.veemag_print_settings_saved'));
    }

    /** Turn the printed edition on or off for one issue. */
    public function toggle(Request $request, VeeMagIssue $issue)
    {
        $issue->print_enabled = ! $issue->print_enabled;
        $issue->save();

        return redirect()->route('backend.veemag-print.index')
            ->with('status', $issue->print_enabled
                ? __('messages.veemag_print_on', ['title' => $issue->title])
                : __('messages.veemag_print_off', ['title' => $issue->title]));
    }

    /** Per-issue price override; blank clears it back to the default. */
    public function price(Request $request, VeeMagIssue $issue)
    {
        $data = $request->validate([
            'print_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $issue->print_price = ($data['print_price'] === null || $data['print_price'] === '')
            ? null
            : $data['print_price'];
        $issue->save();

        return redirect()->route('backend.veemag-print.index')
            ->with('status', __('messages.veemag_print_price_saved', ['title' => $issue->title]));
    }
}
