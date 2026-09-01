<?php

namespace Modules\MediaRadar\Models;

use Illuminate\Database\Eloquent\Model;

class MediaCandidateAnalysis extends Model
{
    protected $table = 'media_candidate_analysis';

    protected $fillable = [
        'candidate_id',
        'analysis_version',
        'editorial_score',
        'relevance_score',
        'source_quality_score',
        'production_quality_score',
        'recency_score',
        'audience_interest_score',
        'originality_score',
        'brand_fit_score',
        'suggested_genre_id',
        'secondary_genre_ids',
        'suggested_title',
        'suggested_description',
        'suggested_summary',
        'suggested_tags_json',
        'topic_entities_json',
        'risk_flags_json',
        'explanation',
        'model_reference',
        'source',
    ];

    protected $casts = [
        'secondary_genre_ids' => 'array',
        'suggested_tags_json' => 'array',
        'topic_entities_json' => 'array',
        'risk_flags_json' => 'array',
    ];

    public function candidate()
    {
        return $this->belongsTo(MediaCandidate::class, 'candidate_id');
    }

    public function riskFlags(): array
    {
        return is_array($this->risk_flags_json) ? $this->risk_flags_json : [];
    }

    public function tags(): array
    {
        return is_array($this->suggested_tags_json) ? $this->suggested_tags_json : [];
    }
}
