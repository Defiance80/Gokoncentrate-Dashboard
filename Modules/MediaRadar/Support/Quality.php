<?php

namespace Modules\MediaRadar\Support;

/**
 * Maps between the dashboard's video_quality constants and pixel heights so a
 * rule can express "at least 1080p" against providers that report resolution
 * in different shapes.
 */
final class Quality
{
    public const LADDER = [
        '480p' => 480,
        '720p' => 720,
        '1080p' => 1080,
        '1440p' => 1440,
        '2K' => 1440,
        '4K' => 2160,
        '8K' => 4320,
    ];

    public static function heightFor(?string $label): ?int
    {
        if ($label === null || $label === '') {
            return null;
        }

        return self::LADDER[$label] ?? null;
    }

    public static function labelForHeight(?int $height): ?string
    {
        if ($height === null || $height <= 0) {
            return null;
        }

        $match = null;

        foreach (self::LADDER as $label => $ladderHeight) {
            if ($height >= $ladderHeight) {
                $match = $label;
            }
        }

        return $match ?? '480p';
    }

    /**
     * YouTube only exposes contentDetails.definition (hd or sd), so "hd" is
     * treated as a floor of 720p rather than an exact resolution.
     */
    public static function heightForYouTubeDefinition(?string $definition): ?int
    {
        return match (strtolower((string) $definition)) {
            'hd' => 720,
            'sd' => 480,
            default => null,
        };
    }

    /**
     * @return bool True when the candidate satisfies the rule's minimum.
     */
    public static function satisfies(?int $candidateHeight, ?string $requiredLabel, bool $strict = false): bool
    {
        $required = self::heightFor($requiredLabel);

        if ($required === null) {
            return true;
        }

        if ($candidateHeight === null) {
            // Nothing reported by the provider: keep it unless the rule insists.
            return ! $strict;
        }

        return $candidateHeight >= $required;
    }
}
