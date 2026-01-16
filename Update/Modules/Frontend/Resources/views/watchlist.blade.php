@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.my_watchlist') }}
@endsection

@section('content')
    <div class="section-spacing">
        <!-- <div class="page-title" id="page_title1">
                <h4 class="m-0 text-center">{{ __('frontend.my_watchlist') }}</h4>
            </div> -->

        <div class="container-fluid">
            <div class="row gy-4">
                <div class="col-lg-3 col-md-4">
                    @include('frontend::components.account-settings-sidebar')
                </div>
                <div class="col-lg-9 col-md-8">
                    <div class="d-flex justify-content-start mb-4">
                        <h4 class="m-0">{{ __('frontend.my_watchlist') }}</h4>
                    </div>
                    <ul class="nav nav-pills justify-content-start comingsoon-tabs" id="unlock-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="unlock-all-tab" data-bs-toggle="pill"
                                data-bs-target="#unlock-all" type="button" role="tab" aria-controls="unlock-all"
                                aria-selected="true">{{ __('messages.all') }}</button>
                        </li>
                        @if(isenablemodule('movie') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="unlock-movie-tab" data-bs-toggle="pill"
                                data-bs-target="#unlock-movie" type="button" role="tab" aria-controls="unlock-movie"
                                aria-selected="false" tabindex="-1">{{ __('messages.movie') }}</button>
                        </li>
                        @endif
                        @if(isenablemodule('tvshow') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="unlock-video-tab" data-bs-toggle="pill"
                                data-bs-target="#unlock-video" type="button" role="tab" aria-controls="unlock-video"
                                aria-selected="false" tabindex="-1">{{ __('messages.tvshow') }}</button>
                        </li>
                        @endif
                        @if(isenablemodule('video') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="unlock-episode-tab" data-bs-toggle="pill"
                                data-bs-target="#unlock-episode" type="button" role="tab"
                                aria-controls="unlock-episode" aria-selected="false" tabindex="-1">{{__('messages.video')}}</button>
                        </li>
                        @endif
                    </ul>
                    <div class="tab-content" id="unlock-tabs-content">
                        <div class="tab-pane fade show active" id="unlock-all" role="tabpanel"
                            aria-labelledby="unlock-all-tab">
                            <div class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                id="watch-list-all">

                            </div>
                            <div class="" id="empty-watch-list-all" style="display: none;">
                                <div class="row flex-column justify-content-center align-items-center">
                                    <div class="col-sm-12 text-center">
                                        <div class="my-5 py-2 add-watch-list-info text-center">
                                            <h4>{{ __('frontend.your_watchlist_empty') }}</h4>
                                            <p class="mb-0 watchlist-description">{{ __('frontend.add_watchlist_content') }}
                                            </p>
                                        </div>
                                        <div>
                                            <a href="{{ route('user.login') }}"> <button class="btn btn-primary">
                                                    {{ __('messages.Explor_Content') }} </button></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(isenablemodule('movie') == 1)
                        <div class="tab-pane fade" id="unlock-movie" role="tabpanel" aria-labelledby="unlock-movie-tab">
                            <div class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                id="watch-list-movie">

                            </div>
                            <div class="" id="empty-watch-list-movie" style="display: none;">
                                <div class="row flex-column justify-content-center align-items-center">
                                    <div class="col-sm-12 text-center">
                                        <div class="my-5 py-2 add-watch-list-info text-center">
                                            <h4>{{ __('frontend.your_watchlist_empty') }}</h4>
                                            <p class="mb-0 watchlist-description">{{ __('frontend.add_watchlist_content') }}
                                            </p>
                                        </div>
                                        <div>
                                            <a href="{{ route('user.login') }}"> <button class="btn btn-primary">
                                                    {{ __('messages.Explor_Content') }} </button></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if(isenablemodule('tvshow') == 1)
                        <div class="tab-pane fade" id="unlock-video" role="tabpanel" aria-labelledby="unlock-video-tab">
                            <div class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                id="watch-list-tvshow">

                            </div>
                            <div class="" id="empty-watch-list-tvshow" style="display: none;">
                                <div class="row flex-column justify-content-center align-items-center">
                                    <div class="col-sm-12 text-center">
                                        <div class="my-5 py-2 add-watch-list-info text-center">
                                            <h4>{{ __('frontend.your_watchlist_empty') }}</h4>
                                            <p class="mb-0 watchlist-description">{{ __('frontend.add_watchlist_content') }}
                                            </p>
                                        </div>
                                        <div>
                                            <a href="{{ route('user.login') }}"> <button class="btn btn-primary">
                                                    {{ __('messages.Explor_Content') }} </button></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if(isenablemodule('video') == 1)
                        <div class="tab-pane fade" id="unlock-episode" role="tabpanel" aria-labelledby="unlock-episode-tab">
                            <div class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                id="watch-list-video">

                            </div>
                            <div class="" id="empty-watch-list-video" style="display: none;">
                                <div class="row flex-column justify-content-center align-items-center">
                                    <div class="col-sm-12 text-center">
                                        <div class="my-5 py-2 add-watch-list-info text-center">
                                            <h4>{{ __('frontend.your_watchlist_empty') }}</h4>
                                            <p class="mb-0 watchlist-description">{{ __('frontend.add_watchlist_content') }}
                                            </p>
                                        </div>
                                        <div>
                                            <a href="{{ route('user.login') }}"> <button class="btn btn-primary">
                                                    {{ __('messages.Explor_Content') }} </button></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="card-style-slider shimmer-container" style="display: none;">
                        <div class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 mt-3">
                            @for ($i = 0; $i < 12; $i++)
                                <div class="col mb-3">
                                    @include('components.card_shimmer_movieList')
                                </div>
                            @endfor
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/entertainment.min.js') }}" defer></script>

    <script>
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const apiUrl = `${baseUrl}/api/watch-list`;
        const csrf_token = '{{ csrf_token() }}';
        const shimmerContainer = document.querySelector('.shimmer-container');

        const paging = {
            all:   { page: 1, perPage: 10, hasMore: true, loading: false, loadedOnce: false },
            @if(isenablemodule('movie') == 1)
            movie: { page: 1, perPage: 10, hasMore: true, loading: false, loadedOnce: false },
            @endif
            @if(isenablemodule('tvshow') == 1)
            tvshow:{ page: 1, perPage: 10, hasMore: true, loading: false, loadedOnce: false },
            @endif
            @if(isenablemodule('video') == 1)
            video: { page: 1, perPage: 10, hasMore: true, loading: false, loadedOnce: false },
            @endif
        };

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('unlock-all-tab').addEventListener('click', () => ensureLoaded('all'));
            @if(isenablemodule('movie') == 1)
            const movieTab = document.getElementById('unlock-movie-tab');
            if (movieTab) movieTab.addEventListener('click', () => ensureLoaded('movie'));
            @endif
            @if(isenablemodule('tvshow') == 1)
            const videoTab = document.getElementById('unlock-video-tab');
            if (videoTab) videoTab.addEventListener('click', () => ensureLoaded('tvshow'));
            @endif
            @if(isenablemodule('video') == 1)
            const episodeTab = document.getElementById('unlock-episode-tab');
            if (episodeTab) episodeTab.addEventListener('click', () => ensureLoaded('video'));
            @endif

            ensureLoaded('all');

            window.addEventListener('scroll', onScrollLoadMore, { passive: true });
        });

        function ensureLoaded(type) {
            if (!paging[type].loadedOnce) {
                paging[type].page = 1;
                paging[type].hasMore = true;
                loadData(type, false);
            }
        }

        function getActiveType() {
            if (document.getElementById('unlock-all').classList.contains('active')) return 'all';
            @if(isenablemodule('movie') == 1)
            if (document.getElementById('unlock-movie') && document.getElementById('unlock-movie').classList.contains('active')) return 'movie';
            @endif
            @if(isenablemodule('tvshow') == 1)
            if (document.getElementById('unlock-video') && document.getElementById('unlock-video').classList.contains('active')) return 'tvshow';
            @endif
            @if(isenablemodule('video') == 1)
            if (document.getElementById('unlock-episode') && document.getElementById('unlock-episode').classList.contains('active')) return 'video';
            @endif
            return 'all';
        }

        function onScrollLoadMore() {
            const type = getActiveType();
            const st = paging[type];
            if (!st.hasMore || st.loading) return;
            if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 400) {
                loadData(type, true);
            }
        }

        function loadData(type, loadMore) {
            const st = paging[type];
            if (!st || st.loading) return;
            st.loading = true;

            const container = document.getElementById(`watch-list-${type}`);
            const emptyContainer = document.getElementById(`empty-watch-list-${type}`);

            const page = loadMore ? (st.page + 1) : st.page;
            const perPage = st.perPage;

            if (!loadMore) {
                if (shimmerContainer) {
                    shimmerContainer.style.display = 'block';
                }
                if (emptyContainer) {
                    emptyContainer.style.display = 'none';
                }
            }
            fetch(`${apiUrl}?type=${type}&is_ajax=1&per_page=${perPage}&page=${page}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrf_token,
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                const html = data && data.html ? data.html : '';
                if (!loadMore) {
                    container.innerHTML = html;
                    st.loadedOnce = true;
                } else if (html) {
                    container.insertAdjacentHTML('beforeend', html);
                }
                emptyContainer.style.display = (container.innerHTML.trim()) ? 'none' : 'block';
                st.hasMore = !!(data && data.hasMore);
                if (st.hasMore && html) st.page = page;
                if (shimmerContainer) {
                    shimmerContainer.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading watchlist:', error);
                if (!loadMore) {
                    container.innerHTML = '';
                    emptyContainer.style.display = 'block';
                }
                if (shimmerContainer) {
                    shimmerContainer.style.display = 'none';
                }
            })
            .finally(() => {
                st.loading = false;
            });
        }
    </script>
@endsection
