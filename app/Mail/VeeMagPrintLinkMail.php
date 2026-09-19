<?php

namespace App\Mail;

use App\Models\VeeMagIssue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sends the reader the Stripe checkout link for a printed VeeMag issue.
 *
 * Deliberately NOT queueable: the platform runs QUEUE_CONNECTION=database
 * with no worker, so a queued mail would never be sent.
 */
class VeeMagPrintLinkMail extends Mailable
{
    use SerializesModels;

    public VeeMagIssue $issue;
    public string $checkoutUrl;
    public float $price;
    public string $currency;

    public function __construct(VeeMagIssue $issue, string $checkoutUrl, float $price, string $currency)
    {
        $this->issue = $issue;
        $this->checkoutUrl = $checkoutUrl;
        $this->price = $price;
        $this->currency = $currency;
    }

    public function build()
    {
        return $this->subject('Your print copy of ' . $this->issue->title)
            ->view('emails.veemag_print_link');
    }
}
