<?php

namespace Modules\MediaRadar\Services;

use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

/**
 * Duplicate prevention.
 *
 * The database enforces UNIQUE(publication_id, provider, provider_video_id);
 * this service is the read side of that guarantee so discovery runs can report
 * "existing" versus "new" without relying on a constraint violation.
 */
class DeduplicationService
{
    public function existing(NormalizedCandidate $candidate, int $publicationId): ?MediaCandidate
    {
        return MediaCandidate::withTrashed()
            ->where('publication_id', $publicationId)
            ->where('provider', $candidate->provider)
            ->where('provider_video_id', $candidate->providerVideoId)
            ->first();
    }

    public function isDuplicate(NormalizedCandidate $candidate, int $publicationId): bool
    {
        return $this->existing($candidate, $publicationId) !== null;
    }

    /**
     * Collapse a provider response to one entry per video before anything is
     * written, so a video returned by several queries in the same run is only
     * processed once.
     *
     * @param  list<NormalizedCandidate>  $candidates
     * @return list<NormalizedCandidate>
     */
    public function unique(array $candidates): array
    {
        $seen = [];

        foreach ($candidates as $candidate) {
            $seen[$candidate->dedupeKey()] = $candidate;
        }

        return array_values($seen);
    }

    /**
     * Videos already published as a movie through a different route, matched on
     * the stored playback URL. Prevents Media Radar re-offering something an
     * editor added by hand.
     */
    public function alreadyPublishedUrls(array $urls): array
    {
        if ($urls === []) {
            return [];
        }

        return MediaCandidate::query()
            ->whereNotNull('published_entertainment_id')
            ->whereIn('provider_url', $urls)
            ->pluck('provider_url')
            ->all();
    }
}
