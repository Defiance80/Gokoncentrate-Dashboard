<?php

namespace Modules\MediaRadar\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Sources\Support\NormalizedCandidate;

/**
 * Cover art for a discovered video.
 *
 * Two modes, chosen in Media Radar settings:
 *
 *  - provider_link  the artwork stays hosted by YouTube / Vimeo and only the
 *                   link is stored. setBaseUrlWithFileName() returns remote
 *                   URLs untouched, so this works everywhere artwork is shown.
 *  - cropped_local  the provider artwork (a frame grab for YouTube, the chosen
 *                   still for Vimeo) is additionally cropped to poster ratio
 *                   with GD and stored beside the other movie artwork.
 *
 * The video itself is never downloaded or re-hosted.
 */
class CoverArtService
{
    public const MODE_PROVIDER_LINK = 'provider_link';

    public const MODE_CROPPED_LOCAL = 'cropped_local';

    /**
     * @return array{thumbnail_url: ?string, poster_url: ?string, cover_art_source: string, cover_art_origin_url: ?string}
     */
    public function resolve(NormalizedCandidate $candidate, MediaRadarSetting $settings): array
    {
        $origin = $this->firstReachable($candidate->thumbnailCandidates ?: array_filter([$candidate->thumbnailUrl]));

        $result = [
            'thumbnail_url' => $origin,
            'poster_url' => $origin,
            'cover_art_source' => self::MODE_PROVIDER_LINK,
            'cover_art_origin_url' => $origin,
        ];

        if ($origin === null || $settings->cover_art_mode !== self::MODE_CROPPED_LOCAL) {
            return $result;
        }

        $fileName = $this->cropPoster($origin, $candidate, (string) $settings->cover_crop_ratio);

        if ($fileName !== null) {
            $result['poster_url'] = $fileName;
            $result['cover_art_source'] = self::MODE_CROPPED_LOCAL;
        }

        return $result;
    }

    /**
     * First artwork URL the provider actually serves. YouTube advertises
     * maxresdefault for every video but only returns it for some.
     *
     * @param  list<string>  $urls
     */
    public function firstReachable(array $urls): ?string
    {
        foreach ($urls as $url) {
            if (empty($url)) {
                continue;
            }

            try {
                $response = Http::timeout(8)->head($url);

                if ($response->successful() && $this->looksLikeRealArtwork($response->header('Content-Length'))) {
                    return $url;
                }
            } catch (\Throwable $e) {
                Log::debug('[MediaRadar] Cover art HEAD failed: '.$url.' '.$e->getMessage());
            }
        }

        return $urls[0] ?? null;
    }

    /**
     * YouTube answers missing sizes with a tiny grey placeholder rather than a
     * 404, so very small responses are treated as absent.
     */
    private function looksLikeRealArtwork(?string $contentLength): bool
    {
        if ($contentLength === null || $contentLength === '') {
            return true;
        }

        return (int) $contentLength > 2048;
    }

    /**
     * Centre-crop the provider artwork to poster ratio and store it with the
     * other movie images. Returns the stored file name, or null on failure.
     */
    public function cropPoster(string $sourceUrl, NormalizedCandidate $candidate, string $ratio = '2:3'): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            Log::warning('[MediaRadar] GD is not available; falling back to the provider artwork link.');

            return null;
        }

        try {
            $response = Http::timeout(20)->get($sourceUrl);

            if (! $response->successful()) {
                return null;
            }

            $source = @imagecreatefromstring($response->body());

            if ($source === false) {
                return null;
            }

            $cropped = $this->blurredFit($source, $ratio);
            imagedestroy($source);

            if ($cropped === null) {
                return null;
            }

            $fileName = $this->fileName($candidate);
            $stored = $this->store($cropped, $fileName);
            imagedestroy($cropped);

            return $stored ? $fileName : null;
        } catch (\Throwable $e) {
            Log::warning('[MediaRadar] Cover art crop failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Compose a poster at the target ratio WITHOUT cropping the artwork: the
     * source image is drawn fully (contained, centered) on top of a blurred,
     * enlarged copy of itself that fills the frame — so any source ratio fits.
     *
     * @param  \GdImage  $source
     * @return \GdImage|null
     */
    private function blurredFit($source, string $ratio)
    {
        [$ratioWidth, $ratioHeight] = $this->parseRatio($ratio);

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW < 1 || $srcH < 1) {
            return null;
        }

        $target = $ratioWidth / $ratioHeight;
        $outW = max(400, (int) config('mediaradar.cover_art.poster_width', 1000));
        $outH = (int) round($outW / $target);

        $canvas = imagecreatetruecolor($outW, $outH);
        if ($canvas === false) {
            return null;
        }

        // --- Blurred background that fills the frame (cover) ---
        // Cover-crop the source to the target ratio, render small, blur, upscale
        // (cheaper + smoother than blurring at full size), then darken slightly.
        $winW = $srcW;
        $winH = (int) round($srcW / $target);
        if ($winH > $srcH) {
            $winH = $srcH;
            $winW = (int) round($srcH * $target);
        }
        $sx = (int) floor(($srcW - $winW) / 2);
        $sy = (int) floor(($srcH - $winH) / 2);

        $smallW = max(1, (int) round($outW / 6));
        $smallH = max(1, (int) round($outH / 6));
        $small = imagecreatetruecolor($smallW, $smallH);
        if ($small !== false) {
            imagecopyresampled($small, $source, 0, 0, $sx, $sy, $smallW, $smallH, $winW, $winH);
            for ($i = 0; $i < 6; $i++) {
                imagefilter($small, IMG_FILTER_GAUSSIAN_BLUR);
            }
            imagecopyresampled($canvas, $small, 0, 0, 0, 0, $outW, $outH, $smallW, $smallH);
            imagedestroy($small);
            imagefilter($canvas, IMG_FILTER_BRIGHTNESS, -40);
        }

        // --- Foreground: the full artwork, contained and centered ---
        $fit = min($outW / $srcW, $outH / $srcH);
        $fgW = max(1, (int) round($srcW * $fit));
        $fgH = max(1, (int) round($srcH * $fit));
        $fx = (int) floor(($outW - $fgW) / 2);
        $fy = (int) floor(($outH - $fgH) / 2);
        imagecopyresampled($canvas, $source, $fx, $fy, 0, 0, $fgW, $fgH, $srcW, $srcH);

        return $canvas;
    }

    /**
     * @param  \GdImage  $source
     * @return \GdImage|null
     */
    private function centreCrop($source, string $ratio)
    {
        [$ratioWidth, $ratioHeight] = $this->parseRatio($ratio);

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            return null;
        }

        $target = $ratioWidth / $ratioHeight;

        // Largest window of the requested ratio that fits inside the frame.
        $cropWidth = $sourceWidth;
        $cropHeight = (int) round($sourceWidth / $target);

        if ($cropHeight > $sourceHeight) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $target);
        }

        $offsetX = (int) floor(($sourceWidth - $cropWidth) / 2);
        // Bias upwards: faces and titles usually sit above centre in a frame.
        $offsetY = (int) floor(($sourceHeight - $cropHeight) * 0.35);

        $outputWidth = (int) config('mediaradar.cover_art.poster_width', 600);
        $outputHeight = (int) round($outputWidth / $target);

        $canvas = imagecreatetruecolor($outputWidth, $outputHeight);

        if ($canvas === false) {
            return null;
        }

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            $offsetX,
            $offsetY,
            $outputWidth,
            $outputHeight,
            $cropWidth,
            $cropHeight
        );

        return $canvas;
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function parseRatio(string $ratio): array
    {
        $parts = explode(':', trim($ratio));

        $width = (float) ($parts[0] ?? 0);
        $height = (float) ($parts[1] ?? 0);

        if ($width <= 0 || $height <= 0) {
            return [2.0, 3.0];
        }

        return [$width, $height];
    }

    public function fileName(NormalizedCandidate $candidate): string
    {
        return sprintf(
            'media-radar-%s-%s-%s.jpg',
            $candidate->provider,
            preg_replace('/[^A-Za-z0-9_-]/', '', $candidate->providerVideoId),
            substr((string) time(), -6)
        );
    }

    /**
     * Stores the poster where the dashboard already keeps movie artwork, so
     * setBaseUrlWithFileName($fileName, 'image', 'movie') resolves it.
     *
     * @param  \GdImage  $image
     */
    private function store($image, string $fileName): bool
    {
        $folder = (string) config('mediaradar.cover_art.storage_folder', 'movie');
        $quality = (int) config('mediaradar.cover_art.jpeg_quality', 85);

        ob_start();
        imagejpeg($image, null, $quality);
        $binary = (string) ob_get_clean();

        if ($binary === '') {
            return false;
        }

        $activeDisk = env('ACTIVE_STORAGE', 'local');
        $disk = Storage::disk($activeDisk);

        $path = $activeDisk === 'local'
            ? 'public/'.$folder.'/image/'.$fileName
            : $folder.'/image/'.$fileName;

        $directory = dirname($path);

        if (! $disk->exists($directory)) {
            if ($activeDisk === 'local') {
                File::makeDirectory(storage_path('app/'.$directory), 0775, true, true);
            } else {
                $disk->makeDirectory($directory);
            }
        }

        return (bool) $disk->put($path, $binary);
    }

    /**
     * Display URL for artwork stored either way.
     */
    public static function displayUrl(?string $value): string
    {
        return setBaseUrlWithFileName((string) $value, 'image', 'movie');
    }
}
