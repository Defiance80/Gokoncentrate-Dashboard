<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One reader's order for the printed edition of a VeeMag issue.
 *
 * The row is written before the reader is sent to Stripe so an abandoned
 * checkout is still visible, and reconciled on return from Stripe.
 */
class VeeMagPrintOrder extends Model
{
    protected $table = 'veemag_print_orders';

    protected $fillable = [
        'issue_id', 'user_id', 'email', 'stripe_session_id', 'checkout_url',
        'amount', 'currency', 'status', 'shipping', 'paid_at',
    ];

    protected $casts = [
        'shipping' => 'array',
        'paid_at'  => 'datetime',
        'amount'   => 'decimal:2',
    ];

    public function issue()
    {
        return $this->belongsTo(VeeMagIssue::class, 'issue_id');
    }
}
