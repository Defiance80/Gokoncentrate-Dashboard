{{-- Standalone Music category rail. Appears only when there are 6 or more music
     videos; below that threshold the videos are folded into the Media Series rail
     (see index.blade), so this rail only ever shows the full category. --}}
@php
    $kmMusic = [];
    if (\Illuminate\Support\Facades\Schema::hasColumn('entertainments', 'is_music')) {
        $kmMusicItems = \Modules\Entertainment\Models\Entertainment::query()
            ->where('type', 'movie')
            ->where('is_music', 1)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit(18)
            ->get();

        if ($kmMusicItems->count() >= 6) {
            $kmMusic = \Modules\Entertainment\Transformers\Backend\CommonContentResourceV3::collection($kmMusicItems)
                ->toArray(request());
        }
    }
@endphp

@if (count($kmMusic) >= 6)
    <div class="GoKoncentrate-block">
        <div class="d-flex align-items-center justify-content-between my-2 me-2">
            <h5 class="main-title text-capitalize mb-0">{{ __('frontend.music') }}</h5>
            <a href="{{ route('music') }}" class="view-all-button text-decoration-none flex-none">
                <span>{{ __('frontend.view_all') }}</span>
                <i class="ph ph-caret-right"></i>
            </a>
        </div>

        <div class="card-style-slider">
            <div class="slick-general" data-items="6.5" data-items-desktop="5.5" data-items-laptop="4.5"
                data-items-tab="3.5" data-items-mobile-sm="3.5" data-items-mobile="2.5" data-speed="1000"
                data-autoplay="false" data-center="false" data-infinite="false" data-navigation="true"
                data-pagination="false" data-spacing="12">
                @include('frontend::components.card.card_movie', ['values' => $kmMusic])
            </div>
        </div>
    </div>
@endif
