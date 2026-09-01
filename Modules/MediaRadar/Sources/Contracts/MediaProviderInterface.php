<?php

namespace Modules\MediaRadar\Sources\Contracts;

use Modules\MediaRadar\Models\MediaDiscoveryRule;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

interface MediaProviderInterface
{
    public function slug(): string;

    public function label(): string;

    /**
     * True when credentials are present and the provider is switched on.
     */
    public function isConfigured(): bool;

    /**
     * Run a discovery mission and return normalized candidates.
     *
     * @return list<NormalizedCandidate>
     */
    public function search(MediaDiscoveryRule $rule): array;

    /**
     * Fetch and normalize a single video by its provider id.
     */
    public function getVideo(string $providerVideoId): ?NormalizedCandidate;

    /**
     * Recent uploads for a watched creator, used instead of burning search quota.
     *
     * @return list<NormalizedCandidate>
     */
    public function getRecentCreatorVideos(string $providerCreatorId, int $limit = 10): array;

    public function canEmbed(NormalizedCandidate $candidate): bool;

    /**
     * Playable URL stored on the published record.
     */
    public function playbackUrl(string $providerVideoId): string;

    /**
     * Value stored in entertainments.video_upload_type.
     */
    public function uploadTypeConstant(): string;

    /**
     * Number of provider HTTP requests made since the adapter was created.
     */
    public function requestCount(): int;
}
