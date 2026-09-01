<?php

namespace Modules\MediaRadar\Services;

use App\Services\ChatGTPService;
use Illuminate\Support\Facades\Log;
use Modules\Genres\Models\Genres;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaCandidateAnalysis;
use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Models\MediaTrustedSource;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;
use Modules\MediaRadar\Support\CandidateStatus;
use Modules\MediaRadar\Support\EditorialScorer;
use Modules\MediaRadar\Support\RuleCriteria;

/**
 * Editorial analysis.
 *
 * The deterministic scorer always runs. The AI pass is optional enrichment on
 * top of it: it can suggest a better title, description, summary and tags, but
 * it can never make a candidate disappear, and a failure leaves the candidate
 * reviewable with its deterministic score intact.
 *
 * Categories are always loaded from the dashboard's own genres table, never
 * hard coded.
 */
class EditorialAnalysisService
{
    public function __construct(
        private EditorialScorer $scorer,
        private CandidateService $candidates,
        private ChatGTPService $chatGpt,
    ) {
    }

    public function analyze(MediaCandidate $candidate, ?MediaDiscoveryRule $rule = null): MediaCandidateAnalysis
    {
        $settings = MediaRadarSetting::getInstance();
        $rule = $rule ?? $candidate->rules()->first();

        $criteria = $rule ? RuleCriteria::fromRule($rule) : new RuleCriteria();
        $normalized = $this->toNormalized($candidate);

        $trusted = $this->trustedSource($candidate);

        $breakdown = $this->scorer->score(
            $normalized,
            $criteria,
            $this->matchedTerms($candidate),
            $trusted !== null,
            $trusted?->approvalRate()
        );

        $ai = $settings->ai_enabled ? $this->requestAiSuggestions($candidate, $rule) : null;

        if (is_array($ai) && isset($ai['editorial_score'])) {
            // Blend, so a single odd AI answer cannot swing a candidate wildly.
            $breakdown['editorial_score'] = (int) round(
                ($breakdown['editorial_score'] * 0.6) + ($ai['editorial_score'] * 0.4)
            );
        }

        $analysis = MediaCandidateAnalysis::create(array_merge($breakdown, [
            'candidate_id' => $candidate->id,
            'analysis_version' => (int) config('mediaradar.analysis.version', 1),
            'suggested_genre_id' => $this->resolveGenreId($ai['suggested_category'] ?? null) ?? $candidate->genre_id ?? $rule?->genre_id,
            'secondary_genre_ids' => $this->resolveGenreIds($ai['secondary_categories'] ?? []),
            'suggested_title' => $ai['suggested_title'] ?? $candidate->original_title,
            'suggested_description' => $ai['description'] ?? $candidate->original_description,
            'suggested_summary' => $ai['summary'] ?? null,
            'suggested_tags_json' => $ai['tags'] ?? [],
            'topic_entities_json' => $ai['topic_entities'] ?? [],
            'risk_flags_json' => $ai['risk_flags'] ?? [],
            'explanation' => $ai['why_selected'] ?? $this->deterministicExplanation($breakdown, $trusted !== null),
            'model_reference' => $ai === null ? null : (string) config('mediaradar.analysis.model_reference'),
            'source' => $ai === null ? 'deterministic' : 'ai',
        ]));

        $this->applyToCandidate($candidate, $analysis);

        return $analysis;
    }

    /**
     * Copy the analysis onto the candidate so an editor edits one place and the
     * publishing bridge reads one place.
     */
    private function applyToCandidate(MediaCandidate $candidate, MediaCandidateAnalysis $analysis): void
    {
        $updates = [
            'editorial_score' => $analysis->editorial_score,
            'genre_id' => $candidate->genre_id ?: $analysis->suggested_genre_id,
        ];

        // Never overwrite wording an editor has already changed.
        if (empty($candidate->editorial_title)) {
            $updates['editorial_title'] = $analysis->suggested_title;
        }

        if (empty($candidate->editorial_description)) {
            $updates['editorial_description'] = $analysis->suggested_description;
        }

        if (empty($candidate->editorial_summary)) {
            $updates['editorial_summary'] = $analysis->suggested_summary;
        }

        if (empty($candidate->editorial_tags)) {
            $updates['editorial_tags'] = $analysis->tags();
        }

        if (empty($candidate->secondary_genre_ids) && ! empty($analysis->secondary_genre_ids)) {
            $updates['secondary_genre_ids'] = $analysis->secondary_genre_ids;
        }

        $this->candidates->transition($candidate, CandidateStatus::READY_FOR_REVIEW, $updates);
    }

    /**
     * Ask the AI for editorial suggestions and validate the shape before it is
     * allowed anywhere near the database.
     *
     * @return array<string, mixed>|null
     */
    public function requestAiSuggestions(MediaCandidate $candidate, ?MediaDiscoveryRule $rule): ?array
    {
        try {
            $raw = $this->chatGpt->GenerateBio($this->buildPrompt($candidate, $rule));
            $decoded = json_decode((string) $raw, true);

            if (isset($decoded['error'])) {
                Log::warning('[MediaRadar] AI analysis error: '.json_encode($decoded['error']));

                return null;
            }

            $content = $decoded['choices'][0]['message']['content'] ?? null;

            if (empty($content)) {
                return null;
            }

            return self::validateAiPayload($content);
        } catch (\Throwable $e) {
            Log::warning('[MediaRadar] AI analysis failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Structured output validation. Anything unexpected is dropped rather than
     * stored.
     *
     * @return array<string, mixed>|null
     */
    public static function validateAiPayload(string $content): ?array
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $payload = json_decode(substr($content, $start, $end - $start + 1), true);

        if (! is_array($payload)) {
            return null;
        }

        $clean = [];

        if (isset($payload['editorial_score']) && is_numeric($payload['editorial_score'])) {
            $clean['editorial_score'] = max(0, min(100, (int) $payload['editorial_score']));
        }

        foreach (['suggested_category', 'suggested_title', 'summary', 'description', 'why_selected'] as $key) {
            if (! empty($payload[$key]) && is_string($payload[$key])) {
                $clean[$key] = trim($payload[$key]);
            }
        }

        foreach (['secondary_categories', 'tags', 'risk_flags', 'topic_entities'] as $key) {
            if (! empty($payload[$key]) && is_array($payload[$key])) {
                $clean[$key] = array_values(array_filter(
                    array_map(static fn ($item) => is_scalar($item) ? trim((string) $item) : null, $payload[$key])
                ));
            }
        }

        return $clean === [] ? null : $clean;
    }

    public function buildPrompt(MediaCandidate $candidate, ?MediaDiscoveryRule $rule): string
    {
        $genres = Genres::where('status', 1)->orderBy('name')->pluck('name')->take(60)->implode(', ');

        $lines = [
            'You are an editorial assistant for the GoKoncentrate streaming platform.',
            'Assess the following third-party video as a programming candidate and reply with JSON only.',
            '',
            'Video title: '.$candidate->original_title,
            'Creator: '.$candidate->creator_name,
            'Platform: '.$candidate->provider,
            'Published: '.optional($candidate->published_at)->toDateString(),
            'Duration (seconds): '.$candidate->duration_seconds,
            'Views: '.$candidate->view_count,
            'Description: '.mb_substr((string) $candidate->original_description, 0, 1200),
            '',
            'Available GoKoncentrate categories: '.$genres,
        ];

        if ($rule !== null) {
            $lines[] = 'Discovery rule: '.$rule->name;
            $lines[] = 'Rule keywords: '.implode(', ', $rule->effectiveSearchTerms());
        }

        $lines[] = '';
        $lines[] = 'Reply with this JSON shape and nothing else: '
            .'{"editorial_score":0-100,"suggested_category":"one of the categories above",'
            .'"secondary_categories":[],"suggested_title":"","summary":"","description":"",'
            .'"tags":[],"risk_flags":[],"why_selected":""}';

        return implode("\n", $lines);
    }

    private function deterministicExplanation(array $breakdown, bool $trusted): string
    {
        return sprintf(
            'Scored %d/100 without AI assistance (relevance %d, source %d, production %d, recency %d, audience %d, originality %d, brand fit %d).%s',
            $breakdown['editorial_score'],
            $breakdown['relevance_score'],
            $breakdown['source_quality_score'],
            $breakdown['production_quality_score'],
            $breakdown['recency_score'],
            $breakdown['audience_interest_score'],
            $breakdown['originality_score'],
            $breakdown['brand_fit_score'],
            $trusted ? ' Creator is on the trusted source list.' : ''
        );
    }

    private function trustedSource(MediaCandidate $candidate): ?MediaTrustedSource
    {
        if (empty($candidate->provider_creator_id)) {
            return null;
        }

        return MediaTrustedSource::query()
            ->where('publication_id', $candidate->publication_id)
            ->where('provider', $candidate->provider)
            ->where('provider_creator_id', $candidate->provider_creator_id)
            ->where('enabled', true)
            ->first();
    }

    /**
     * @return list<string>
     */
    private function matchedTerms(MediaCandidate $candidate): array
    {
        $terms = [];

        foreach ($candidate->ruleMatches as $match) {
            foreach ((array) $match->matched_terms as $term) {
                $terms[] = (string) $term;
            }
        }

        return array_values(array_unique($terms));
    }

    private function toNormalized(MediaCandidate $candidate): NormalizedCandidate
    {
        return new NormalizedCandidate(
            provider: (string) $candidate->provider,
            providerVideoId: (string) $candidate->provider_video_id,
            providerUrl: $candidate->provider_url,
            providerCreatorId: $candidate->provider_creator_id,
            creatorName: $candidate->creator_name,
            creatorUrl: $candidate->creator_url,
            originalTitle: $candidate->original_title,
            originalDescription: $candidate->original_description,
            publishedAt: optional($candidate->published_at)->toIso8601String(),
            durationSeconds: $candidate->duration_seconds,
            language: $candidate->language,
            height: $candidate->height,
            qualityVerified: (bool) $candidate->quality_verified,
            viewCount: $candidate->view_count,
            likeCount: $candidate->like_count,
            commentCount: $candidate->comment_count,
            embeddable: (bool) $candidate->embeddable,
            thumbnailUrl: $candidate->thumbnail_url,
        );
    }

    private function resolveGenreId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $genre = Genres::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first();

        return $genre?->id;
    }

    /**
     * @param  array<int, string>  $names
     * @return list<int>
     */
    private function resolveGenreIds(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            $id = $this->resolveGenreId(is_string($name) ? $name : null);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
