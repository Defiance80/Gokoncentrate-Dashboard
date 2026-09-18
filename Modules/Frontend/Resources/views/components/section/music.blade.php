{{-- Music rail for the home gallery, sitting with Media Series in the media/network
     area. Rendered server-side (like every other rail) so the global slick init
     sizes the cards correctly and they match the other rails' aspect ratio.
     Appears only when there are 6 or more music videos. --}}
@php
    // Two placements share this rail:
    //  - Category rail (default): the standalone Music category, shown only with 6+.
    //  - Fallback sub-row ($fallback = true): shown in the media area for 1..5 items,
    //    i.e. only when the dedicated Music category rail is hidden, so the videos
    //    are never lost.
    $kmFallback = $fallback ?? false;
    $kmMusic = [];
    $kmMusicShow = false;

    if (\Illuminate\Support\Facades\Schema::hasColumn('entertainments', 'is_music')) {
        $kmMusicItems = \Modules\Entertainment\Models\Entertainment::query()
            ->where('type', 'movie')
            ->where('is_music', 1)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit(18)
            ->get();

        $kmCount = $kmMusicItems->count();
        $kmMusicShow = $kmFallback ? ($kmCount >= 1 && $kmCount < 6) : ($kmCount >= 6);

        if ($kmMusicShow) {
            $kmMusic = \Modules\Entertainment\Transformers\Backend\CommonContentResourceV3::collection($kmMusicItems)
                ->toArray(request());
        }
    }
@endphp

@if ($kmMusicShow && count($kmMusic) >= 1)
    <div class="GoKoncentrate-block">
        <div class="d-flex align-items-center justify-content-between my-2 me-2">
            <h5 class="main-title text-capitalize mb-0">{{ __('frontend.music') }}</h5>
            @if (count($kmMusic) > 6)
                <a href="{{ route('music') }}" class="view-all-button text-decoration-none flex-none">
                    <span>{{ __('frontend.view_all') }}</span>
                    <i class="ph ph-caret-right"></i>
                </a>
            @endif
        </div>

        <div class="card-style-slider {{ count($kmMusic) <= 6 ? 'slide-data-less' : '' }}">
            <div class="slick-general" data-items="6.5" data-items-desktop="5.5" data-items-laptop="4.5"
                data-items-tab="3.5" data-items-mobile-sm="3.5" data-items-mobile="2.5" data-speed="1000"
                data-autoplay="false" data-center="false" data-infinite="false" data-navigation="true"
                data-pagination="false" data-spacing="12">
                @include('frontend::components.card.card_movie', ['values' => $kmMusic])
            </div>
        </div>
    </div>
@endif
