<?php

namespace Modules\MediaRadar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MediaEditorialDecision extends Model
{
    protected $table = 'media_editorial_decisions';

    protected $fillable = [
        'candidate_id',
        'editor_id',
        'decision',
        'rejection_reason',
        'notes',
        'previous_status',
        'new_status',
        'ip_address',
    ];

    public function candidate()
    {
        return $this->belongsTo(MediaCandidate::class, 'candidate_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
