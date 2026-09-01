<?php

namespace Modules\MediaRadar\Sources\Support;

/**
 * Provider-neutral representation of one discovered video.
 *
 * Nothing downstream of the adapters knows whether a candidate came from
 * YouTube or Vimeo. Deliberately framework free so it can be unit tested.
 */
final class NormalizedCandidate
{
    public function __construct(
        public string $provider,
        public string $providerVideoId,
        public ?string $providerUrl = null,
        public ?string $providerCreatorId = null,
        public ?string $creatorName = null,
        public ?string $creatorUrl = null,
        public ?string $originalTitle = null,
        public ?string $originalDescription = null,
        public ?string $publishedAt = null,
        public ?int $durationSeconds = null,
        public ?string $language = null,
        public ?int $height = null,
        public bool $qualityVerified = false,
        public ?int $viewCount = null,
        public ?int $likeCount = null,
        public ?int $commentCount = null,
        public bool $embeddable = true,
        public ?string $thumbnailUrl = null,
        /** @var list<string> */
        public array $thumbnailCandidates = [],
        /** @var list<string> */
        public array $tags = [],
    ) {
    }

    /**
     * Stable identity used for de-duplication.
     */
    public function dedupeKey(): string
    {
        return $this->provider.':'.$this->providerVideoId;
    }

    public function publishedYear(): ?int
    {
        if (empty($this->publishedAt)) {
            return null;
        }

        $timestamp = strtotime($this->publishedAt);

        return $timestamp === false ? null : (int) date('Y', $timestamp);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'provider_video_id' => $this->providerVideoId,
            'provider_url' => $this->providerUrl,
            'provider_creator_id' => $this->providerCreatorId,
            'creator_name' => $this->creatorName,
            'creator_url' => $this->creatorUrl,
            'original_title' => $this->originalTitle,
            'original_description' => $this->originalDescription,
            'published_at' => $this->publishedAt,
            'duration_seconds' => $this->durationSeconds,
            'language' => $this->language,
            'height' => $this->height,
            'quality_verified' => $this->qualityVerified,
            'view_count' => $this->viewCount,
            'like_count' => $this->likeCount,
            'comment_count' => $this->commentCount,
            'embeddable' => $this->embeddable,
            'thumbnail_url' => $this->thumbnailUrl,
            'tags' => $this->tags,
        ];
    }

    /**
     * Free text used by keyword / excluded-term / actor matching.
     */
    public function searchableText(): string
    {
        return strtolower(trim(implode(' ', array_filter([
            $this->originalTitle,
            $this->originalDescription,
            $this->creatorName,
            implode(' ', $this->tags),
        ]))));
    }
}
