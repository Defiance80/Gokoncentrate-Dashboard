@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.veemags') }}
@endsection

@push('after-styles')
<style>
    .veemag-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px}
    @media (min-width:768px){.veemag-grid{grid-template-columns:repeat(auto-fill,minmax(180px,1fr))}}
    .veemag-grid .slick-item{width:100%;margin:0}
</style>
@endpush

@section('content')
    <div class="list-page">
        <div class="movie-lists section-spacing-bottom">
            <div class="container-fluid">
                <h4 class="mb-1">{{ __('frontend.veemags') }}</h4>
                <p class="text-secondary mb-3">{{ __('frontend.issues') }}</p>

                @if ($issues->count() > 0)
                    <div class="veemag-grid">
                        @include('frontend::components.card.card_veemag', ['values' => $issues])
                    </div>
                @else
                    <div class="text-center py-5">
                        <img src="{{ asset('img/NoData.png') }}" alt="No Data" style="max-width:220px;margin:0 auto;display:block;">
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
