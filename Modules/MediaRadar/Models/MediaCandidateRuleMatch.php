<?php

namespace Modules\MediaRadar\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One video matched by one rule. A video matched by five rules produces five
 * rows here and still exactly one candidate.
 */
class MediaCandidateRuleMatch extends Model
{
    protected $table = 'media_candidate_rule_matches';

    protected $fillable = [
        'candidate_id',
        'rule_id',
        'run_id',
        'matched_at',
        'matched_terms',
        'match_metadata_json',
    ];

    protected $casts = [
        'matched_at' => 'datetime',
        'matched_terms' => 'array',
        'match_metadata_json' => 'array',
    ];

    public function candidate()
    {
        return $this->belongsTo(MediaCandidate::class, 'candidate_id');
    }

    public function rule()
    {
        return $this->belongsTo(MediaDiscoveryRule::class, 'rule_id')->withTrashed();
    }
}
