<?php

namespace App\Mail;

use App\Models\VeeMagLead;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the sales inbox that a reader asked about advertising from
 * inside a VeeMag issue.
 *
 * Deliberately NOT queueable, for the same reason as VeeMagPrintLinkMail:
 * the platform runs QUEUE_CONNECTION=database with no worker, so a queued
 * mail would never leave the server.
 */
class VeeMagAdEnquiryMail extends Mailable
{
    use SerializesModels;

    public VeeMagLead $lead;
    public string $issueTitle;
    public string $sectionTitle;

    public function __construct(VeeMagLead $lead, string $issueTitle = '', string $sectionTitle = '')
    {
        $this->lead = $lead;
        $this->issueTitle = $issueTitle;
        $this->sectionTitle = $sectionTitle;
    }

    public function build()
    {
        $who = $this->lead->business_name ?: $this->lead->name;

        return $this->subject('Advertising enquiry: ' . $who)
            ->view('emails.veemag_ad_enquiry');
    }
}
