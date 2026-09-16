@extends('frontend::layouts.master', ['entertainment' => $entertainment])

@section('title')
    {{ $data['data']['name'] ?? '' }}
@endsection

@push('after-styles')
<style>
    /* Podcast detail: a focused, episode-first "shift through" layout. */
    .podcast-detail .podcast-eyebrow{display:inline-flex;align-items:center;gap:.5rem;font-size:.72rem;letter-spacing:.16em;text-transform:uppercase;color:var(--bs-primary);font-weight:700;margin-bottom:.5rem}
    .podcast-detail #seasons{scroll-margin-top:90px}
    .podcast-detail .episodes-head{display:flex;align-items:center;gap:.6rem;margin:0 0 1rem}
    .podcast-detail .episodes-head i{color:var(--bs-primary)}
</style>
@endpush

@section('content')

    @php
        $data = $data['data'];
    @endphp

    <div class="podcast-detail">
        <div id="thumbnail-section">
            @include('frontend::components.section.thumbnail', [
                'data' => $data['trailer_url'],
                'type' => $data['trailer_url_type'],
                'thumbnail_image' => $data['thumbnail_image'],
                'subtitle_info' => '',
                'content_type' => 'tvshow',
                'content_id' => $data['id'],
                'video_type' => $data['video_upload_type'],
                'content_video_type' => 'trailer',
            ])
        </div>

        <div id="detail-section">
            <div class="container-fluid pt-4">
                <span class="podcast-eyebrow"><i class="ph-fill ph-microphone"></i> {{ __('frontend.media_series') }} &middot; {{ __('frontend.podcast') }}</span>
            </div>
            <div id="tvshow-id">
                @include('frontend::components.section.data_detail', ['data' => $data, 'subtitle_info' => ''])
            </div>
        </div>

        <div class="container-fluid mt-4">
            <div class="episodes-head">
                <i class="ph ph-queue align-middle fs-4"></i>
                <h4 class="mb-0">{{ __('frontend.episodes') }}</h4>
            </div>
            <div id="seasons">
                @include('frontend::components.section.seasons', ['data' => $data['tvShowLinks']])
            </div>
        </div>

        @if($data['is_clips_enabled'])
            @include('frontend::components.section.clips_trailers', ['clips' => $data['clips'] ?? []])
        @endif

        <div class="container-fluid padding-right-0 mt-4">
            <div class="overflow-hidden">
                @if ($data['casts'] != null)
                    <div id="movie-cast" class="half-spacing">
                        @include('frontend::components.section.castcrew', [
                            'data' => $data['casts']->toArray(request()),
                            'title' => __('frontend.casts'),
                            'entertainment_id' => $data['id'],
                            'type' => 'actor',
                            'slug' => '',
                        ])
                    </div>
                @endif
            </div>
        </div>

        <div class="container-fluid">
            <div id="add-review">
                @include('frontend::components.section.add_review', ['addreview' => 'Add Review'])
            </div>
            @if ($data['three_reviews'] != null)
                <div id="review-list">
                    @include('frontend::components.section.review_list', [
                        'data' => $data['three_reviews']->toArray(request()),
                        'your_review' => $data['your_review'],
                        'title' => $data['name'],
                        'total_review' => count($data['reviews']),
                    ])
                </div>
            @endif
        </div>

        <div class="container-fluid padding-right-0">
            <div class="overflow-hidden">
                @if ($data['more_items'] != null && count($data['more_items']) > 0)
                    <div id="more-like-this">
                        @include('frontend::components.section.entertainment', [
                            'data' => $data['more_items']->toArray(request()),
                            'title' => __('frontend.more_like_this'),
                            'type' => $data['type'],
                            'slug' => '',
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="DeviceSupport" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content position-relative">
                <div class="modal-body user-login-card m-0 p-4 position-relative">
                    <button type="button" class="btn btn-primary custom-close-btn rounded-2" data-bs-dismiss="modal">
                        <i class="ph ph-x text-white fw-bold align-middle"></i>
                    </button>
                    <div class="modal-body">{{ __('frontend.device_not_support') }}</div>
                    <div class="d-flex align-items-center justify-content-center">
                        <a href="{{ Auth::check() ? route('subscriptionPlan') : route('login') }}"
                            class="btn btn-primary mt-5">{{ __('frontend.upgrade') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
