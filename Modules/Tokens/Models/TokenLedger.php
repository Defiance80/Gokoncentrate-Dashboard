<?php

namespace Modules\Tokens\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenLedger extends BaseModel
{
    protected $table = 'token_ledgers';

    protected $fillable = [
        'user_id',
        'amount_cents',
        'type',
        'source',
        'reason',
        'admin_id',
        'metadata',
        'balance_after_cents',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'balance_after_cents' => 'integer',
        'metadata' => 'array',
    ];

    // Transaction types
    const TYPE_EARNED = 'earned';
    const TYPE_SPENT = 'spent';
    const TYPE_ADMIN_CREDIT = 'admin_credit';
    const TYPE_ADMIN_DEBIT = 'admin_debit';
    const TYPE_ADMIN_SET = 'admin_set';
    const TYPE_REFUND = 'refund';

    /**
     * Get all transaction types with labels
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_EARNED => __('tokens::tokens.type_earned'),
            self::TYPE_SPENT => __('tokens::tokens.type_spent'),
            self::TYPE_ADMIN_CREDIT => __('tokens::tokens.type_admin_credit'),
            self::TYPE_ADMIN_DEBIT => __('tokens::tokens.type_admin_debit'),
            self::TYPE_ADMIN_SET => __('tokens::tokens.type_admin_set'),
            self::TYPE_REFUND => __('tokens::tokens.type_refund'),
        ];
    }

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Admin who performed the action
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get formatted amount (e.g., "+12.34" or "-5.00")
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->amount_cents >= 0 ? '+' : '';
        return $sign . number_format($this->amount_cents / 100, 2, '.', '');
    }

    /**
     * Get formatted balance after
     */
    public function getFormattedBalanceAfterAttribute(): string
    {
        return number_format($this->balance_after_cents / 100, 2, '.', '');
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        $types = self::getTypes();
        return $types[$this->type] ?? $this->type;
    }

    /**
     * Get type badge HTML
     */
    public function getTypeBadgeAttribute(): string
    {
        $badgeClass = match ($this->type) {
            self::TYPE_EARNED => 'bg-success',
            self::TYPE_SPENT => 'bg-danger',
            self::TYPE_ADMIN_CREDIT => 'bg-info',
            self::TYPE_ADMIN_DEBIT => 'bg-warning text-dark',
            self::TYPE_ADMIN_SET => 'bg-primary',
            self::TYPE_REFUND => 'bg-secondary',
            default => 'bg-secondary',
        };

        return '<span class="badge ' . $badgeClass . '">' . $this->type_label . '</span>';
    }

    /**
     * Scope for user's ledger entries
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for admin-initiated actions
     */
    public function scopeAdminActions($query)
    {
        return $query->whereIn('type', [
            self::TYPE_ADMIN_CREDIT,
            self::TYPE_ADMIN_DEBIT,
            self::TYPE_ADMIN_SET,
        ]);
    }

    /**
     * Scope for credits (positive amounts)
     */
    public function scopeCredits($query)
    {
        return $query->where('amount_cents', '>', 0);
    }

    /**
     * Scope for debits (negative amounts)
     */
    public function scopeDebits($query)
    {
        return $query->where('amount_cents', '<', 0);
    }
}
