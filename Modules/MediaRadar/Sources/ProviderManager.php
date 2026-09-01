<?php

namespace Modules\MediaRadar\Sources;

use Modules\MediaRadar\Sources\Contracts\MediaProviderInterface;
use Modules\MediaRadar\Sources\Vimeo\VimeoClient;
use Modules\MediaRadar\Sources\Vimeo\VimeoNormalizer;
use Modules\MediaRadar\Sources\Vimeo\VimeoProvider;
use Modules\MediaRadar\Sources\YouTube\YouTubeClient;
use Modules\MediaRadar\Sources\YouTube\YouTubeNormalizer;
use Modules\MediaRadar\Sources\YouTube\YouTubeProvider;

/**
 * Resolves provider adapters by slug. Adding a source later means adding one
 * case here plus the adapter itself; nothing else in Media Radar changes.
 */
class ProviderManager
{
    /** @var array<string, MediaProviderInterface> */
    private array $resolved = [];

    public const SUPPORTED = ['youtube', 'vimeo'];

    public function make(string $slug): MediaProviderInterface
    {
        $slug = strtolower(trim($slug));

        if (isset($this->resolved[$slug])) {
            return $this->resolved[$slug];
        }

        $provider = match ($slug) {
            'youtube' => new YouTubeProvider(
                new YouTubeClient((array) config('mediaradar.providers.youtube', [])),
                new YouTubeNormalizer(),
            ),
            'vimeo' => new VimeoProvider(
                new VimeoClient((array) config('mediaradar.providers.vimeo', [])),
                new VimeoNormalizer(),
            ),
            default => throw new \InvalidArgumentException('Unknown media provider ['.$slug.'].'),
        };

        return $this->resolved[$slug] = $provider;
    }

    /**
     * A fresh adapter, used when per-run request counters must start at zero.
     */
    public function fresh(string $slug): MediaProviderInterface
    {
        unset($this->resolved[strtolower(trim($slug))]);

        return $this->make($slug);
    }

    public function supports(string $slug): bool
    {
        return in_array(strtolower(trim($slug)), self::SUPPORTED, true);
    }

    /**
     * @return array<string, string> slug => label
     */
    public function options(): array
    {
        $options = [];

        foreach (self::SUPPORTED as $slug) {
            $options[$slug] = $this->make($slug)->label();
        }

        return $options;
    }

    /**
     * @return list<MediaProviderInterface>
     */
    public function configured(): array
    {
        $providers = [];

        foreach (self::SUPPORTED as $slug) {
            $provider = $this->make($slug);

            if ($provider->isConfigured()) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }
}
