@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.media_series') }}
@endsection

@push('after-styles')
<style>
    /* Podcasts read as a denser, smaller card than the rest of the catalogue. */
    .podcast-scope .podcast-card .image-box{border-radius:12px;overflow:hidden;}
    .podcast-scope .podcast-card img{aspect-ratio:1/1;object-fit:cover;}
</style>
@endpush

@section('content')
    <div class="list-page podcast-scope">
        <div class="movie-lists section-spacing-bottom">
            <div class="container-fluid">
                <h4 class="mb-1">{{ __('frontend.media_series') }}</h4>
                <p class="text-secondary mb-3">{{ __('frontend.media_series_sub') }}</p>
                <div class="row gy-4 row-cols-3 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-8"
                    id="entertainment-list">
                </div>
                <div class="card-style-slider shimmer-container">
                    <div class="row gy-4 row-cols-3 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-8 mt-3">
                        @for ($i = 0; $i < 12; $i++)
                            <div class="shimmer-container col mb-3">
                                @include('components.card_shimmer_movieList')
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/entertainment.min.js') }}" defer></script>
    <script>
        const noDataImageSrc = '{{ asset('img/NoData.png') }}';
        const shimmerContainer = document.querySelector('.shimmer-container');
        const EntertainmentList = document.getElementById('entertainment-list');
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        let per_page = 18;

        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const apiUrl = `${baseUrl}/api/v3/tvshow-list?is_ajax=1&per_page=${per_page}&is_podcast=1`;

        const showNoDataImage = () => {
            shimmerContainer.innerHTML = '';
            const noDataImage = document.createElement('img');
            noDataImage.src = noDataImageSrc;
            noDataImage.alt = 'No Data Found';
            noDataImage.style.display = 'block';
            noDataImage.style.margin = '0 auto';
            shimmerContainer.appendChild(noDataImage);
        };

        const loadData = async () => {
            if (!hasMore || isLoading) return;
            isLoading = true;
            shimmerContainer.style.display = '';
            try {
                const response = await fetch(`${apiUrl}&page=${currentPage}`);
                const data = await response.json();
                if (data?.html) {
                    EntertainmentList.insertAdjacentHTML(currentPage === 1 ? 'afterbegin' : 'beforeend', data.html);
                    if (window.initTrailerHover) window.initTrailerHover();
                    hasMore = !!data.hasMore;
                    if (hasMore) currentPage++;
                    shimmerContainer.style.display = 'none';
                } else {
                    showNoDataImage();
                }
            } catch (error) {
                console.error('Fetch error:', error);
                showNoDataImage();
            } finally {
                isLoading = false;
            }
        };

        const handleScroll = () => {
            if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500 && hasMore) {
                loadData();
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            loadData();
            window.addEventListener('scroll', handleScroll);
        });
    </script>
@endsection
