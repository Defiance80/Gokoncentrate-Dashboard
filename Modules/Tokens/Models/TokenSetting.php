<?php

namespace Modules\Tokens\Models;

use App\Models\BaseModel;
use Illuminate\Support\Facades\Cache;

class TokenSetting extends BaseModel
{
    protected $table = 'token_settings';

    protected $fillable = [
        'token_label',
        'token_name',
        'token_usd_cents_per_token',
        'earn_cents',
        'earn_seconds',
        'daily_cap_cents',
        'global_enabled',
        'repeat_cooldown_seconds',
        'eligible_content_flags',
    ];

    protected $casts = [
        'token_usd_cents_per_token' => 'integer',
        'earn_cents' => 'integer',
        'earn_seconds' => 'integer',
        'daily_cap_cents' => 'integer',
        'global_enabled' => 'boolean',
        'repeat_cooldown_seconds' => 'integer',
        'eligible_content_flags' => 'array',
    ];

    /**
     * Get the singleton settings instance (first row or create default)
     */
    public static function getInstance(): self
    {
        return Cache::remember('token_settings', 3600, function () {
            return self::firstOrCreate(
                ['id' => 1],
                [
                    'token_label' => 'KT',
                    'token_name' => 'GoKoncentrate Tokens',
                    'token_usd_cents_per_token' => 100,
                    'earn_cents' => 1,
                    'earn_seconds' => 10,
                    'global_enabled' => true,
                    'eligible_content_flags' => [
                        'free_video' => true,
                        'free_magazine' => true,
                        'focus_mode' => true,
                    ],
                ]
            );
        });
    }

    /**
     * Clear cache when settings are updated
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('token_settings');
        });
    }

    /**
     * Check if content type is eligible for earning
     */
    public function isContentEligible(string $contentType): bool
    {
        $flags = $this->eligible_content_flags ?? [];
        return $flags[$contentType] ?? false;
    }

    /**
     * Get formatted token value (e.g., "1.00" for 100 cents)
     */
    public function getFormattedTokenValueAttribute(): string
    {
        return number_format($this->token_usd_cents_per_token / 100, 2, '.', '');
    }

    /**
     * Get formatted earn rate (e.g., "0.01 KT per 10 seconds")
     */
    public function getFormattedEarnRateAttribute(): string
    {
        $earnKT = number_format($this->earn_cents / 100, 2, '.', '');
        return "{$earnKT} {$this->token_label} per {$this->earn_seconds} seconds";
    }
}
