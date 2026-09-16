{{-- Self-contained VeeMags rail for the home gallery. Renders nothing when
     there are no published issues, so an empty section never shows. --}}
@php
    $veemagIssues = \App\Models\VeeMagIssue::published()
        ->orderByDesc('release_date')->orderByDesc('id')->limit(18)->get();
@endphp

@if ($veemagIssues->count() > 0)
    <div class="GoKoncentrate-block">
        <div class="d-flex align-items-center justify-content-between my-2 me-2">
            <h5 class="main-title text-capitalize mb-0">{{ __('frontend.veemags') }}</h5>
        </div>

        <div class="card-style-slider {{ $veemagIssues->count() <= 6 ? 'slide-data-less' : '' }}">
            <div class="slick-general slick-general-veemag" data-items="6.5" data-items-desktop="5.5"
                data-items-laptop="4.5" data-items-tab="3.5" data-items-mobile-sm="3.5"
                data-items-mobile="2.5" data-speed="1000" data-autoplay="false" data-center="false"
                data-infinite="false" data-navigation="true" data-pagination="false" data-spacing="12">
                @include('frontend::components.card.card_veemag', ['values' => $veemagIssues])
            </div>
        </div>
    </div>
@endif
