<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * GoKoncentrate Token (KT) Service
 *
 * Manages user token balances for the in-app virtual currency.
 * All amounts are stored in cents (100 cents = 1 KT = $1.00)
 *
 * Accrual: 1 cent per 10 seconds of qualified viewing
 */
class TokenService
{
    /**
     * Get user's token balance in cents
     */
    public function getBalanceCents(User $user): int
    {
        return (int) $user->token_balance_cents;
    }

    /**
     * Get user's token balance formatted as decimal string (e.g., "12.34")
     */
    public function getBalanceFormatted(User $user): string
    {
        return number_format($this->getBalanceCents($user) / 100, 2, '.', '');
    }

    /**
     * Get full token data array for API responses
     */
    public function getTokenData(User $user): array
    {
        return [
            'token_balance_cents' => $this->getBalanceCents($user),
            'token_balance' => $this->getBalanceFormatted($user),
            'token_label' => 'KT',
        ];
    }

    /**
     * Credit tokens to user account
     *
     * @param User $user
     * @param int $amountCents Amount in cents to credit
     * @param string|null $reason Optional reason for the credit
     * @return bool
     */
    public function credit(User $user, int $amountCents, ?string $reason = null): bool
    {
        if ($amountCents <= 0) {
            return false;
        }

        return DB::transaction(function () use ($user, $amountCents) {
            $user->increment('token_balance_cents', $amountCents);
            $user->token_updated_at = now();
            $user->save();
            return true;
        });
    }

    /**
     * Debit tokens from user account
     *
     * @param User $user
     * @param int $amountCents Amount in cents to debit
     * @param string|null $reason Optional reason for the debit
     * @return bool Returns false if insufficient balance
     */
    public function debit(User $user, int $amountCents, ?string $reason = null): bool
    {
        if ($amountCents <= 0) {
            return false;
        }

        if ($user->token_balance_cents < $amountCents) {
            return false; // Insufficient balance
        }

        return DB::transaction(function () use ($user, $amountCents) {
            $user->decrement('token_balance_cents', $amountCents);
            $user->token_updated_at = now();
            $user->save();
            return true;
        });
    }

    /**
     * Set user's token balance to a specific amount (admin use)
     *
     * @param User $user
     * @param int $amountCents New balance in cents
     * @return bool
     */
    public function setBalance(User $user, int $amountCents): bool
    {
        if ($amountCents < 0) {
            return false;
        }

        $user->token_balance_cents = $amountCents;
        $user->token_updated_at = now();
        return $user->save();
    }

    /**
     * Credit tokens based on watch time
     * Accrual rate: 1 cent per 10 seconds
     *
     * @param User $user
     * @param int $watchTimeSeconds Total watch time in seconds
     * @return int Amount of cents credited
     */
    public function creditForWatchTime(User $user, int $watchTimeSeconds): int
    {
        $centsToCredit = (int) floor($watchTimeSeconds / 10);

        if ($centsToCredit > 0) {
            $this->credit($user, $centsToCredit, 'watch_time_reward');
        }

        return $centsToCredit;
    }
}
