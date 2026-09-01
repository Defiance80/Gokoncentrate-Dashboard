<?php

namespace Modules\MediaRadar\Support;

final class Duration
{
    /**
     * Parse an ISO-8601 duration (YouTube contentDetails.duration) to seconds.
     */
    public static function fromIso8601(?string $value): ?int
    {
        if (empty($value)) {
            return null;
        }

        if (! preg_match('/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?)?$/i', $value, $m)) {
            return null;
        }

        $days = (int) ($m[1] ?? 0);
        $hours = (int) ($m[2] ?? 0);
        $minutes = (int) ($m[3] ?? 0);
        $seconds = (int) round((float) ($m[4] ?? 0));

        $total = ($days * 86400) + ($hours * 3600) + ($minutes * 60) + $seconds;

        return $total > 0 ? $total : null;
    }

    /**
     * The dashboard stores movie duration as HH:MM.
     */
    public static function toHoursMinutes(?int $seconds): ?string
    {
        if ($seconds === null || $seconds <= 0) {
            return null;
        }

        $minutes = (int) round($seconds / 60);

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public static function humanize(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '-';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remaining);
        }

        return sprintf('%d:%02d', $minutes, $remaining);
    }

    /**
     * YouTube search.list only accepts short / medium / long. Translate the
     * rule window into the closest bucket, or null when it spans several.
     */
    public static function toYouTubeBucket(?int $min, ?int $max): ?string
    {
        if ($min === null && $max === null) {
            return null;
        }

        $min = $min ?? 0;
        $max = $max ?? PHP_INT_MAX;

        if ($max <= 240) {
            return 'short';
        }

        if ($min >= 1200) {
            return 'long';
        }

        if ($min >= 240 && $max <= 1200) {
            return 'medium';
        }

        return null;
    }
}
