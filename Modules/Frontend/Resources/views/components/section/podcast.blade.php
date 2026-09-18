{{-- Media Series (Podcasts) rail for the home gallery. Rendered server-side (like
     every other rail) so the global slick init sizes the cards correctly.
     Appears only when there are 6 or more media-series items. --}}
@php
    $kmPodcast = [];
    if (\Illuminate\Support\Facades\Schema::hasColumn('entertainments', 'is_podcast')) {
        $kmPodcastItems = \Modules\Entertainment\Models\Entertainment::query()
            ->where('type', 'tvshow')
            ->where('is_podcast', 1)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit(18)
            ->get();

        if ($kmPodcastItems->count() >= 6) {
            $kmPodcast = \Modules\Entertainment\Transformers\Backend\CommonContentResourceV3::collection($kmPodcastItems)
                ->toArray(request());
        }
    }
@endphp

@if (count($kmPodcast) >= 6)
    <div class="GoKoncentrate-block podcast-scope">
        <div class="d-flex align-items-center justify-content-between my-2 me-2">
            <h5 class="main-title text-capitalize mb-0">{{ __('frontend.media_series') }}</h5>
            @if (count($kmPodcast) > 6)
                <a href="{{ route('media-series') }}" class="view-all-button text-decoration-none flex-none">
                    <span>{{ __('frontend.view_all') }}</span>
                    <i class="ph ph-caret-right"></i>
                </a>
            @endif
        </div>

        <div class="card-style-slider {{ count($kmPodcast) <= 6 ? 'slide-data-less' : '' }}">
            <div class="slick-general" data-items="6.5" data-items-desktop="5.5" data-items-laptop="4.5"
                data-items-tab="3.5" data-items-mobile-sm="3.5" data-items-mobile="2.5" data-speed="1000"
                data-autoplay="false" data-center="false" data-infinite="false" data-navigation="true"
                data-pagination="false" data-spacing="12">
                @include('frontend::components.card.card_podcast', ['values' => $kmPodcast])
            </div>
        </div>
    </div>
@endif
