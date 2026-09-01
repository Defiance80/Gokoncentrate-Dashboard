<?php

namespace Modules\MediaRadar\Models;

use Illuminate\Database\Eloquent\Model;

class MediaBlockedSource extends Model
{
    protected $table = 'media_blocked_sources';

    protected $fillable = [
        'publication_id',
        'provider',
        'provider_creator_id',
        'creator_name',
        'reason',
        'blocked_by',
    ];

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
