<?php

namespace Modules\MediaRadar\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Genres\Models\Genres;

class MediaTrustedSource extends Model
{
    protected $table = 'media_trusted_sources';

    protected $fillable = [
        'publication_id',
        'provider',
        'provider_creator_id',
        'creator_name',
        'creator_url',
        'enabled',
        'default_genre_id',
        'priority',
        'auto_analyze',
        'publishing_mode',
        'approval_count',
        'rejection_count',
        'last_checked_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_analyze' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    public const PUBLISHING_MODES = [
        'approval_required' => 'Approval required',
        'auto_publish' => 'Auto publish',
        'discovery_only' => 'Discovery only',
    ];

    public function genre()
    {
        return $this->belongsTo(Genres::class, 'default_genre_id');
    }

    public function approvalRate(): ?float
    {
        $total = (int) $this->approval_count + (int) $this->rejection_count;

        if ($total === 0) {
            return null;
        }

        return round(((int) $this->approval_count / $total) * 100, 1);
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
