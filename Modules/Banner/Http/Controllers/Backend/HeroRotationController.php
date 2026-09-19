<?php

namespace Modules\Banner\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Modules\Banner\Models\Banner;
use Modules\Banner\Services\HeroRotationService;

/**
 * Admin control for hero slider rotation.
 *
 * Curation wins: every slide can be locked, and a locked slide is never
 * touched by the weekly refresh.
 */
class HeroRotationController extends Controller
{
    private array $keys = [
        'hero_rotate_enabled',
        'hero_rotate_min_views',
        'hero_rotate_min_width',
    ];

    public function index(HeroRotationService $rotation)
    {
        $settings = [];
        foreach ($this->keys as $key) {
            $settings[$key] = Setting::where('name', $key)->value('val');
        }
        $settings['hero_rotate_min_views'] = $settings['hero_rotate_min_views'] ?: '25';
        $settings['hero_rotate_min_width'] = $settings['hero_rotate_min_width'] ?: '1280';

        $slides = Banner::where('banner_for', 'home')->orderBy('id')->get();

        // Shown so the admin can see whether the view data supports rotating
        // at all, rather than wondering why nothing changed.
        $candidates = $rotation->trendingCandidates((int) $settings['hero_rotate_min_views']);

        return view('banner::backend.hero.index', compact('settings', 'slides', 'candidates'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'hero_rotate_enabled'   => ['nullable', 'boolean'],
            'hero_rotate_min_views' => ['required', 'integer', 'min:1'],
            'hero_rotate_min_width' => ['required', 'integer', 'min:320'],
        ]);

        Setting::add('hero_rotate_enabled', (string) (int) ($data['hero_rotate_enabled'] ?? 0), 'string', 'misc');
        Setting::add('hero_rotate_min_views', (string) $data['hero_rotate_min_views'], 'string', 'misc');
        Setting::add('hero_rotate_min_width', (string) $data['hero_rotate_min_width'], 'string', 'misc');

        return redirect()->route('backend.hero-rotation.index')
            ->with('status', __('messages.hero_settings_saved'));
    }

    /** Lock or unlock one slide. */
    public function toggleLock(Banner $banner)
    {
        $banner->is_locked = ! $banner->is_locked;
        $banner->save();

        return redirect()->route('backend.hero-rotation.index')
            ->with('status', $banner->is_locked
                ? __('messages.hero_slide_locked')
                : __('messages.hero_slide_unlocked'));
    }

    /** Opt one slide in or out of automatic replacement. */
    public function toggleAuto(Banner $banner)
    {
        $banner->auto_managed = ! $banner->auto_managed;
        $banner->save();

        return redirect()->route('backend.hero-rotation.index')
            ->with('status', __('messages.hero_slide_updated'));
    }

    /** Run a rotation now. Preview by default; ?apply=1 writes. */
    public function rotateNow(Request $request, HeroRotationService $rotation)
    {
        $apply = $request->boolean('apply');
        $result = $rotation->rotate(! $apply);

        $summary = $apply
            ? __('messages.hero_rotated', ['replaced' => $result['replaced'], 'kept' => $result['skipped']])
            : __('messages.hero_preview', ['replaced' => $result['replaced'], 'kept' => $result['skipped']]);

        return redirect()->route('backend.hero-rotation.index')
            ->with('status', $summary)
            ->with('rotation_notes', $result['notes']);
    }
}
