@php
    $viewAllRoute = null;
    switch ($type) {
        case 'tvshow':
            $viewAllRoute = route('tv-shows');
            break;
        default:
            $viewAllRoute = route('movies');
    }

    $slickClasses = [
        'latest_movie' => 'slick-general-latest-movie',
        'popular_movie' => 'slick-general-popular-movie',
        'popular_tvshow' => 'slick-general-popular-tvshow',
        'free_movie' => 'slick-general-free-movie',
        'based_on_last_watch' => 'slick-general-last-watch',
        'most-like' => 'slick-general-most-like',
        'most-view' => 'slick-general-most-view',
        'tranding-in-country' => 'slick-general-tranding-country',
    ];

    $class = 'slick-general ' . ($slickClasses[$slug] ?? '');

@endphp

<div class="streamit-block">
    <div class="d-flex align-items-center justify-content-between my-2 me-2">
        <h5 class="main-title text-capitalize mb-0">{{ $title }}</h5>

        @if (count($data) > 6)
            <a href="{{ $viewAllRoute }}" class="view-all-button text-decoration-none flex-none">
                <span>{{ __('frontend.view_all') }}</span>
                <i class="ph ph-caret-right"></i>
            </a>
        @endif
    </div>

    <div class="card-style-slider {{ count($data) <= 6 ? 'slide-data-less' : '' }}">
        <div class="{{ $class }}" data-items="6.5" data-items-desktop="5.5" data-items-laptop="4.5"
            data-items-tab="3.5" data-items-mobile-sm="3.5" data-items-mobile="2.5" data-speed="1000"
            data-autoplay="false" data-center="false" data-infinite="false" data-navigation="true"
            data-pagination="false" data-spacing="12">





            @if ($type == 'movie')
                @include('frontend::components.card.card_movie', ['values' => $data])
            @else
                @include('frontend::components.card.card_tvshow', ['values' => $data])
            @endif



        </div>
    </div>
</div>
