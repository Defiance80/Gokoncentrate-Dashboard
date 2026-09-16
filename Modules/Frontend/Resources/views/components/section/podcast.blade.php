{{-- Self-contained Media Series (Podcasts) rail for the home gallery. Loads via
     the same API the section uses, renders the smaller podcast card, and stays
     hidden until content exists so an empty section never shows. --}}
@php
    $kmHasPodcasts = \Illuminate\Support\Facades\Schema::hasColumn('entertainments', 'is_podcast')
        && \Illuminate\Support\Facades\DB::table('entertainments')
            ->where('type', 'tvshow')->where('is_podcast', 1)->where('status', 1)
            ->whereNull('deleted_at')->exists();
@endphp

@if ($kmHasPodcasts)
    <div class="GoKoncentrate-block podcast-scope" id="podcast-rail-block" style="display:none;">
        <div class="d-flex align-items-center justify-content-between my-2 me-2">
            <h5 class="main-title text-capitalize mb-0">{{ __('frontend.media_series') }}</h5>
            <a href="{{ route('media-series') }}" class="btn btn-link p-0">{{ __('frontend.view_all') ?? 'View all' }}</a>
        </div>
        <div class="card-style-slider">
            <div class="slick-general slick-general-podcast" id="podcast-rail" data-items="7.5" data-items-desktop="6.5"
                data-items-laptop="5.5" data-items-tab="4.5" data-items-mobile-sm="3.5"
                data-items-mobile="2.5" data-speed="1000" data-autoplay="false" data-center="false"
                data-infinite="false" data-navigation="true" data-pagination="false" data-spacing="10"></div>
        </div>
    </div>

    @push('after-styles')
    <style>
        .podcast-scope .podcast-card .image-box{border-radius:12px;overflow:hidden;}
        .podcast-scope .podcast-card img{aspect-ratio:1/1;object-fit:cover;}
    </style>
    @endpush

    @push('after-scripts')
    <script>
        (function () {
            var base = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
            fetch(base + '/api/v3/tvshow-list?is_ajax=1&per_page=18&is_podcast=1')
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d && d.html) {
                        var rail = document.getElementById('podcast-rail');
                        rail.innerHTML = d.html;
                        document.getElementById('podcast-rail-block').style.display = '';
                        if (window.initTrailerHover) window.initTrailerHover();
                    }
                })
                .catch(function () {});
        })();
    </script>
    @endpush
@endif
