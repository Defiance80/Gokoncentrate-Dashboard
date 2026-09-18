{{-- Self-contained Music rail for the home gallery, next to Media Series. Loads
     music videos (movies flagged is_music) and stays hidden until content exists. --}}
@php
    $kmHasMusic = \Illuminate\Support\Facades\Schema::hasColumn('entertainments', 'is_music')
        && \Illuminate\Support\Facades\DB::table('entertainments')
            ->where('type', 'movie')->where('is_music', 1)->where('status', 1)
            ->whereNull('deleted_at')->exists();
@endphp

@if ($kmHasMusic)
    <div class="GoKoncentrate-block music-scope" id="music-rail-block" style="display:none;">
        <div class="d-flex align-items-center justify-content-between my-2 me-2">
            <h5 class="main-title text-capitalize mb-0">{{ __('frontend.music') }}</h5>
            <a href="{{ route('music') }}" class="btn btn-link p-0">{{ __('frontend.view_all') ?? 'View all' }}</a>
        </div>
        <div class="card-style-slider">
            <div class="slick-general slick-general-music" id="music-rail" data-items="7.5" data-items-desktop="6.5"
                data-items-laptop="5.5" data-items-tab="4.5" data-items-mobile-sm="3.5"
                data-items-mobile="2.5" data-speed="1000" data-autoplay="false" data-center="false"
                data-infinite="false" data-navigation="true" data-pagination="false" data-spacing="10"></div>
        </div>
    </div>

    @push('after-styles')
    <style>
        .music-scope .iq-card .image-box{border-radius:12px;overflow:hidden;}
        .music-scope .iq-card img{aspect-ratio:1/1;object-fit:cover;}
    </style>
    @endpush

    @push('after-scripts')
    <script>
        (function () {
            var base = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
            fetch(base + '/api/v3/movie-list?is_ajax=1&per_page=18&is_music=1')
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d && d.html) {
                        var rail = document.getElementById('music-rail');
                        rail.innerHTML = d.html;
                        document.getElementById('music-rail-block').style.display = '';
                        if (window.initTrailerHover) window.initTrailerHover();
                    }
                })
                .catch(function () {});
        })();
    </script>
    @endpush
@endif
