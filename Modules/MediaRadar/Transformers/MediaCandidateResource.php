<?php

namespace Modules\MediaRadar\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\MediaRadar\Services\CoverArtService;

class MediaCandidateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'publication_id' => $this->publication_id,

            'provider' => $this->provider,
            'provider_video_id' => $this->provider_video_id,
            'provider_url' => $this->provider_url,
            'embed_url' => $this->embedUrl(),
            'embeddable' => (bool) $this->embeddable,

            'creator' => [
                'id' => $this->provider_creator_id,
                'name' => $this->creator_name,
                'url' => $this->creator_url,
            ],

            'original_title' => $this->original_title,
            'original_description' => $this->original_description,

            'title' => $this->displayTitle(),
            'description' => $this->displayDescription(),
            'summary' => $this->editorial_summary,
            'tags' => $this->editorial_tags ?: [],

            'genre' => $this->whenLoaded('genre', fn () => [
                'id' => $this->genre?->id,
                'name' => $this->genre?->name,
            ]),
            'genre_id' => $this->genre_id,

            'artwork' => [
                'thumbnail' => CoverArtService::displayUrl($this->thumbnail_url),
                'poster' => CoverArtService::displayUrl($this->poster_url),
                'source' => $this->cover_art_source,
            ],

            'published_at' => optional($this->published_at)->toIso8601String(),
            'duration_seconds' => $this->duration_seconds,
            'duration' => $this->durationLabel(),
            'quality' => $this->quality_label,
            'quality_verified' => (bool) $this->quality_verified,
            'language' => $this->language,

            'metrics' => [
                'views' => $this->view_count,
                'likes' => $this->like_count,
                'comments' => $this->comment_count,
            ],

            'editorial_score' => $this->editorial_score,
            'score_band' => $this->scoreBand(),
            'status' => $this->status,

            'approved_at' => optional($this->approved_at)->toIso8601String(),
            'rejected_at' => optional($this->rejected_at)->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'scheduled_for' => optional($this->scheduled_for)->toIso8601String(),
            'published_entertainment_id' => $this->published_entertainment_id,

            'discovered_at' => optional($this->discovered_at)->toIso8601String(),
            'analysis' => $this->whenLoaded('analysis', fn () => [
                'editorial_score' => $this->analysis?->editorial_score,
                'suggested_title' => $this->analysis?->suggested_title,
                'suggested_summary' => $this->analysis?->suggested_summary,
                'tags' => $this->analysis?->tags(),
                'risk_flags' => $this->analysis?->riskFlags(),
                'explanation' => $this->analysis?->explanation,
                'source' => $this->analysis?->source,
            ]),
        ];
    }
}
