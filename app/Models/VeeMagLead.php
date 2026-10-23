<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An advertiser enquiry raised from a house ad slot in the VeeMag reader.
 * Rows are kept even when the notification email fails, so no enquiry is
 * lost to a mail outage.
 */
class VeeMagLead extends Model
{
    protected $table = 'veemag_leads';

    protected $fillable = [
        'name',
        'business_name',
        'phone',
        'address',
        'issue_id',
        'section_id',
        'placement',
        'source',
        'mail_sent',
    ];

    protected $casts = [
        'mail_sent' => 'boolean',
    ];
}
