<?php

namespace Modules\MediaRadar\Models;

use App\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Genres\Models\Genres;

class MediaDiscoveryRule extends BaseModel
{
    use SoftDeletes;

    protected $table = 'media_discovery_rules';

    protected $fillable = [
        'publication_id',
        'name',
        'description',
        'enabled',
        'providers',
        'genre_id',
        'secondary_genre_ids',
        'search_terms',
        'excluded_terms',
        'keywords',
        'actors',
        'content_type',
        'min_duration_seconds',
        'max_duration_seconds',
        'release_year_from',
        'release_year_to',
        'min_quality',
        'quality_strict',
        'language',
        'region',
        'published_within_days',
        'min_view_count',
        'minimum_editorial_score',
        'preferred_creator_ids',
        'blocked_creator_ids',
        'auto_approve',
        'destination_movie_access',
        'destination_plan_id',
        'destination_is_restricted',
        'priority',
        'schedule_type',
        'schedule_expression',
        'interval_hours',
        'last_run_at',
        'next_run_at',
        'last_error',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'quality_strict' => 'boolean',
        'destination_is_restricted' => 'boolean',
        'auto_approve' => 'boolean',
        'providers' => 'array',
        'secondary_genre_ids' => 'array',
        'search_terms' => 'array',
        'excluded_terms' => 'array',
        'keywords' => 'array',
        'actors' => 'array',
        'preferred_creator_ids' => 'array',
        'blocked_creator_ids' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public const CONTENT_TYPES = [
        'any' => 'Any',
        'documentary' => 'Documentary',
        'feature' => 'Feature',
        'short' => 'Short',
        'series' => 'Series / Episode',
        'interview' => 'Interview',
        'performance' => 'Performance',
        'trailer' => 'Trailer',
    ];

    public const SCHEDULE_TYPES = [
        'interval' => 'Every N hours',
        'cron' => 'Cron expression',
        'manual' => 'Manual only',
    ];

    public function genre()
    {
        return $this->belongsTo(Genres::class, 'genre_id');
    }

    public function runs()
    {
        return $this->hasMany(MediaDiscoveryRun::class, 'rule_id');
    }

    public function ruleMatches()
    {
        return $this->hasMany(MediaCandidateRuleMatch::class, 'rule_id');
    }

    public function candidates()
    {
        return $this->belongsToMany(
            MediaCandidate::class,
            'media_candidate_rule_matches',
            'rule_id',
            'candidate_id'
        );
    }

    /**
     * Always returns a clean list of strings, whatever the column holds.
     *
     * @return list<string>
     */
    public function arrayValue(string $attribute): array
    {
        $value = $this->{$attribute};

        if (is_string($value)) {
            $value = json_decode($value, true) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $clean = [];

        foreach ($value as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item !== '') {
                $clean[] = $item;
            }
        }

        return $clean;
    }

    /**
     * @return list<string>
     */
    public function providerSlugs(): array
    {
        $slugs = array_map('strtolower', $this->arrayValue('providers'));

        return array_values(array_intersect($slugs, ['youtube', 'vimeo']));
    }

    /**
     * Search terms actually sent to the providers. Only platform and genre are
     * required on a rule, so the genre name is the fallback query and any
     * keywords or actor names widen it.
     *
     * @return list<string>
     */
    public function effectiveSearchTerms(): array
    {
        $terms = array_merge(
            $this->arrayValue('search_terms'),
            $this->arrayValue('keywords'),
            $this->arrayValue('actors')
        );

        if ($terms === []) {
            $genreName = optional($this->genre)->name;

            if (! empty($genreName)) {
                $terms[] = $genreName;
            }
        }

        if ($terms === []) {
            $terms[] = (string) $this->name;
        }

        return array_values(array_unique($terms));
    }

    public function publishedAfter(): ?Carbon
    {
        if ($this->published_within_days) {
            return now()->subDays((int) $this->published_within_days);
        }

        if ($this->release_year_from) {
            return Carbon::createFromDate((int) $this->release_year_from, 1, 1)->startOfDay();
        }

        return null;
    }

    public function publishedBefore(): ?Carbon
    {
        if ($this->release_year_to) {
            return Carbon::createFromDate((int) $this->release_year_to, 12, 31)->endOfDay();
        }

        return null;
    }

    public function isDue(?Carbon $now = null): bool
    {
        $now = $now ?? now();

        if (! $this->enabled || $this->schedule_type === 'manual') {
            return false;
        }

        return $this->next_run_at === null || $this->next_run_at->lessThanOrEqualTo($now);
    }

    public function calculateNextRun(?Carbon $from = null): Carbon
    {
        $from = $from ?? now();
        $hours = max(1, (int) ($this->interval_hours ?: 6));

        return $from->copy()->addHours($hours);
    }

    /**
     * Per-rule override of the global auto-approval switch.
     */
    public function autoApproveEnabled(MediaRadarSetting $settings): bool
    {
        if ($this->auto_approve === null) {
            return (bool) $settings->auto_approve;
        }

        return (bool) $this->auto_approve;
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
