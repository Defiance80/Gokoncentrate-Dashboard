<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\VeeMagIssue;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * VeeMag viewer: the issue detail page and the section-orchestrating player.
 * On the surface a VeeMag is just a cover in the gallery; opening it reveals
 * the distinct issue experience (spec v1.0, sections 4-7).
 */
class VeeMagController extends Controller
{
    /** Issue detail: cinematic hero + table of contents. */
    public function detail(string $slug): View
    {
        $issue = VeeMagIssue::published()->with(['publication', 'sections'])
            ->where('slug', $slug)->firstOrFail();

        return view('frontend::veemag.detail', [
            'issue' => $issue,
            'sections' => $issue->sections,
        ]);
    }

    /** The player: plays the issue as an ordered sequence of sections. */
    public function watch(string $slug): View
    {
        $issue = VeeMagIssue::published()->with(['publication', 'sections'])
            ->where('slug', $slug)->firstOrFail();

        // Hand the player a compact playlist. YouTube/Vimeo ids are extracted
        // so the client can embed via the IFrame APIs.
        $playlist = $issue->sections->map(function ($s) {
            return [
                'id' => $s->id,
                'type' => $s->type,
                'type_label' => $s->type_label,
                'title' => $s->title,
                'subtitle' => $s->subtitle,
                'runtime' => $s->runtime_seconds,
                'provider' => strtolower((string) $s->video_upload_type),
                'video_id' => $this->videoId($s->video_upload_type, $s->video_url_input),
                'src' => $s->video_upload_type === 'Local'
                    ? setBaseUrlWithFileName($s->video_url_input, 'video', 'veemag')
                    : $s->video_url_input,
                'transition' => $s->transition_style,
            ];
        })->values();

        return view('frontend::veemag.watch', [
            'issue' => $issue,
            'playlist' => $playlist,
        ]);
    }

    private function videoId(?string $type, ?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (Str::lower((string) $type) === 'youtube') {
            if (preg_match('~(?:youtu\.be/|watch\?v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $url, $m)) {
                return $m[1];
            }
        }
        if (Str::lower((string) $type) === 'vimeo') {
            if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }
}
