<?php

namespace Modules\MediaRadar\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row configuration for Media Radar, following the same shape as the
 * Tokens module's TokenSetting.
 */
class MediaRadarSetting extends Model
{
    protected $table = 'media_radar_settings';

    protected $fillable = [
        'publication_id',
        'enabled',
        'auto_approve',
        'auto_approve_min_score',
        'auto_publish_after_approval',
        'default_publish_status',
        'default_movie_access',
        'default_plan_id',
        'default_is_restricted',
        'cover_art_mode',
        'cover_crop_ratio',
        'ai_enabled',
        'candidate_expiration_days',
        'youtube_enabled',
        'vimeo_enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_approve' => 'boolean',
        'auto_publish_after_approval' => 'boolean',
        'default_publish_status' => 'boolean',
        'default_is_restricted' => 'boolean',
        'ai_enabled' => 'boolean',
        'youtube_enabled' => 'boolean',
        'vimeo_enabled' => 'boolean',
        'auto_approve_min_score' => 'integer',
        'candidate_expiration_days' => 'integer',
    ];

    public const COVER_ART_MODES = [
        'provider_link' => 'Use the YouTube / Vimeo artwork link',
        'cropped_local' => 'Crop a poster from the provider artwork',
    ];

    public static function getInstance(): self
    {
        $setting = static::query()->first();

        if ($setting === null) {
            $setting = static::create([
                'publication_id' => (int) config('mediaradar.default_publication_id', 1),
            ]);
        }

        return $setting;
    }

    public function providerEnabled(string $slug): bool
    {
        return match (strtolower($slug)) {
            'youtube' => (bool) $this->youtube_enabled,
            'vimeo' => (bool) $this->vimeo_enabled,
            // No dedicated settings column yet; gated by config (env) instead.
            'archive' => (bool) config('mediaradar.providers.archive.enabled', true),
            'dailymotion' => (bool) config('mediaradar.providers.dailymotion.enabled', true),
            default => false,
        };
    }
}
