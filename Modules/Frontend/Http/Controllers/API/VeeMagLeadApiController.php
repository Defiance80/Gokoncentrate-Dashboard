<?php

namespace Modules\Frontend\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\VeeMagAdEnquiryMail;
use App\Models\VeeMagIssue;
use App\Models\VeeMagLead;
use App\Models\VeeMagSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Receives advertiser enquiries raised from the house ad slots inside the
 * VeeMag reader.
 *
 * The row is written first and the email sent second, so a mail outage
 * costs us a notification but never the enquiry itself.
 */
class VeeMagLeadApiController extends Controller
{
    /** Where enquiries are sent. Overridable without a deploy. */
    private function inbox(): string
    {
        return (string) (config('mail.veemag_leads_to') ?: 'info@gokoncentrate.com');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:191'],
            'business_name' => ['nullable', 'string', 'max:191'],
            'phone'         => ['nullable', 'string', 'max:64'],
            'address'       => ['nullable', 'string', 'max:500'],
            'issue_id'      => ['nullable', 'integer'],
            'section_id'    => ['nullable', 'integer'],
            'placement'     => ['nullable', 'string', 'max:64'],
        ]);

        $lead = VeeMagLead::create($data + ['source' => 'app', 'mail_sent' => false]);

        $issueTitle = $lead->issue_id
            ? (string) optional(VeeMagIssue::find($lead->issue_id))->title
            : '';
        $sectionTitle = $lead->section_id
            ? (string) optional(VeeMagSection::find($lead->section_id))->title
            : '';

        try {
            Mail::to($this->inbox())->send(
                new VeeMagAdEnquiryMail($lead, $issueTitle, $sectionTitle)
            );
            $lead->update(['mail_sent' => true]);
        } catch (\Throwable $e) {
            // The enquiry is already saved; surface the failure in the log
            // rather than making the reader think their details were lost.
            Log::error('VeeMag advertising enquiry email failed: ' . $e->getMessage());
        }

        return response()->json([
            'status'  => true,
            'message' => 'Thanks - our team will be in touch shortly.',
        ]);
    }
}
