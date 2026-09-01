<?php

namespace Modules\MediaRadar\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Entertainment\Models\Entertainment;
use Modules\Genres\Models\Genres;
use Modules\MediaRadar\Support\CandidateStatus;
use Modules\MediaRadar\Support\Duration;

class MediaCandidate extends BaseModel
{
    use SoftDeletes;

    protected $table = 'media_candidates';

    protected $fillable = [
        'publication_id',
        'provider',
        'provider_video_id',
        'provider_url',
        'provider_creator_id',
        'creator_name',
        'creator_url',
        'original_title',
        'original_description',
        'published_at',
        'duration_seconds',
        'language',
        'height',
        'quality_label',
        'quality_verified',
        'view_count',
        'like_count',
        'comment_count',
        'embeddable',
        'thumbnail_url',
        'poster_url',
        'cover_art_source',
        'cover_art_origin_url',
        'editorial_title',
        'editorial_description',
        'editorial_summary',
        'editorial_tags',
        'genre_id',
        'secondary_genre_ids',
        'matched_actor_names',
        'editorial_score',
        'status',
        'discovered_at',
        'last_checked_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'scheduled_for',
        'published_entertainment_id',
        'published_at_local',
        'error_message',
        'failed_attempts',
    ];

    protected $casts = [
        'embeddable' => 'boolean',
        'quality_verified' => 'boolean',
        'editorial_tags' => 'array',
        'secondary_genre_ids' => 'array',
        'matched_actor_names' => 'array',
        'published_at' => 'datetime',
        'discovered_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'published_at_local' => 'datetime',
    ];

    public function genre()
    {
        return $this->belongsTo(Genres::class, 'genre_id');
    }

    public function analysis()
    {
        return $this->hasOne(MediaCandidateAnalysis::class, 'candidate_id')->latestOfMany();
    }

    public function analyses()
    {
        return $this->hasMany(MediaCandidateAnalysis::class, 'candidate_id');
    }

    public function decisions()
    {
        return $this->hasMany(MediaEditorialDecision::class, 'candidate_id')->orderByDesc('id');
    }

    public function ruleMatches()
    {
        return $this->hasMany(MediaCandidateRuleMatch::class, 'candidate_id');
    }

    public function rules()
    {
        return $this->belongsToMany(
            MediaDiscoveryRule::class,
            'media_candidate_rule_matches',
            'candidate_id',
            'rule_id'
        );
    }

    public function publishedMovie()
    {
        return $this->belongsTo(Entertainment::class, 'published_entertainment_id');
    }

    /**
     * Title an editor sees and that is carried into the published movie.
     */
    public function displayTitle(): string
    {
        return (string) ($this->editorial_title ?: $this->original_title);
    }

    public function displayDescription(): string
    {
        return (string) ($this->editorial_description ?: $this->original_description);
    }

    public function durationLabel(): string
    {
        return Duration::humanize($this->duration_seconds);
    }

    public function isReviewable(): bool
    {
        return in_array($this->status, CandidateStatus::reviewable(), true);
    }

    public function canTransitionTo(string $status): bool
    {
        return CandidateStatus::canTransition((string) $this->status, $status);
    }

    public function scoreBand(): string
    {
        $score = (int) $this->editorial_score;

        return match (true) {
            $score >= 90 => 'priority',
            $score >= 75 => 'recommended',
            $score >= 60 => 'secondary',
            default => 'low',
        };
    }

    public function scoreBandLabel(): string
    {
        return match ($this->scoreBand()) {
            'priority' => 'Priority Review',
            'recommended' => 'Recommended',
            'secondary' => 'Secondary',
            default => 'Low Priority',
        };
    }

    /**
     * Provider embed reference stored on the published record.
     */
    public function playbackUrl(): string
    {
        if (! empty($this->provider_url)) {
            return (string) $this->provider_url;
        }

        return $this->provider === 'vimeo'
            ? 'https://vimeo.com/'.$this->provider_video_id
            : 'https://www.youtube.com/watch?v='.$this->provider_video_id;
    }

    public function embedUrl(): string
    {
        return $this->provider === 'vimeo'
            ? 'https://player.vimeo.com/video/'.$this->provider_video_id
            : 'https://www.youtube.com/embed/'.$this->provider_video_id;
    }

    public function scopeStatus($query, $status)
    {
        return empty($status) ? $query : $query->where('status', $status);
    }

    public function scopeAwaitingReview($query)
    {
        return $query->whereIn('status', CandidateStatus::reviewable());
    }
}
