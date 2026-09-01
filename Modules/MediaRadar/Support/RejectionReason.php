<?php

namespace Modules\MediaRadar\Support;

final class RejectionReason
{
    public const OPTIONS = [
        'poor_quality' => 'Poor quality',
        'wrong_topic' => 'Wrong topic',
        'weak_brand_fit' => 'Weak brand fit',
        'clickbait' => 'Clickbait',
        'unreliable_source' => 'Unreliable source',
        'duplicate' => 'Duplicate',
        'too_old' => 'Too old',
        'low_production_quality' => 'Low production quality',
        'misleading' => 'Misleading',
        'embedding_unavailable' => 'Embedding unavailable',
        'blocked_creator' => 'Blocked creator',
        'other' => 'Other',
    ];

    public static function isValid(?string $reason): bool
    {
        return $reason !== null && array_key_exists($reason, self::OPTIONS);
    }

    public static function label(?string $reason): string
    {
        return self::OPTIONS[$reason] ?? '-';
    }
}
