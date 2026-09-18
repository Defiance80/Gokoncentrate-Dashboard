@extends('frontend::layouts.master')

@section('title'){{ __('frontend.music') }}@endsection

@push('after-styles')
<style>
    .music-scope .iq-card .image-box{border-radius:12px;overflow:hidden;}
    .music-scope .iq-card img{aspect-ratio:1/1;object-fit:cover;}
</style>
@endpush

@section('content')
    <div class="list-page music-scope">
        <div class="movie-lists section-spacing-bottom">
            <div class="container-fluid">
                <h4 class="mb-1">{{ __('frontend.music') }}</h4>
                <p class="text-secondary mb-3">Music videos across every style.</p>
                <div class="row gy-4 row-cols-3 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-8" id="entertainment-list"></div>
                <div class="card-style-slider shimmer-container">
                    <div class="row gy-4 row-cols-3 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 row-cols-xl-8 mt-3">
                        @for ($i = 0; $i < 12; $i++)
                            <div class="shimmer-container col mb-3">@include('components.card_shimmer_movieList')</div>
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
        let currentPage = 1, isLoading = false, hasMore = true, per_page = 18;
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const apiUrl = `${baseUrl}/api/v3/movie-list?is_ajax=1&per_page=${per_page}&is_music=1`;
        const showNoDataImage = () => { shimmerContainer.innerHTML=''; const i=document.createElement('img'); i.src=noDataImageSrc; i.alt='No Data'; i.style.display='block'; i.style.margin='0 auto'; shimmerContainer.appendChild(i); };
        const loadData = async () => {
            if (!hasMore || isLoading) return; isLoading = true; shimmerContainer.style.display = '';
            try {
                const r = await fetch(`${apiUrl}&page=${currentPage}`); const d = await r.json();
                if (d?.html) { EntertainmentList.insertAdjacentHTML(currentPage===1?'afterbegin':'beforeend', d.html); if (window.initTrailerHover) window.initTrailerHover(); hasMore = !!d.hasMore; if (hasMore) currentPage++; shimmerContainer.style.display='none'; }
                else { showNoDataImage(); }
            } catch (e) { showNoDataImage(); } finally { isLoading = false; }
        };
        const handleScroll = () => { if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500 && hasMore) loadData(); };
        document.addEventListener('DOMContentLoaded', () => { loadData(); window.addEventListener('scroll', handleScroll); });
    </script>
@endsection
