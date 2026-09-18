@php
    // Each section appears in the nav only when it actually has content, so the
    // front end never shows a blank category. Counts are cached (5 min).
    $hasPodcastCol = \Illuminate\Support\Facades\Schema::hasColumn('entertainments', 'is_podcast');

    $kmMovieCount = \Illuminate\Support\Facades\Cache::remember('nav_movie_count', 300, function () {
        return \Illuminate\Support\Facades\DB::table('entertainments')->where('type', 'movie')->where('status', 1)->whereNull('deleted_at')->where('release_date', '<=', now())->count();
    });
    $kmTvShowCount = \Illuminate\Support\Facades\Cache::remember('nav_tvshow_count', 300, function () use ($hasPodcastCol) {
        $q = \Illuminate\Support\Facades\DB::table('entertainments')->where('type', 'tvshow')->where('is_veemag', 0)->where('status', 1)->whereNull('deleted_at');
        if ($hasPodcastCol) { $q->where('is_podcast', 0); }
        return $q->count();
    });
    // VeeMags = the VeeMag platform (published issues), what the home rail shows.
    $kmVeeMagCount = \Illuminate\Support\Facades\Cache::remember('nav_veemag_count', 300, function () {
        return \Illuminate\Support\Facades\Schema::hasTable('veemag_issues')
            ? \Illuminate\Support\Facades\DB::table('veemag_issues')->where('status', 'published')->whereNull('deleted_at')->count()
            : 0;
    });
    $kmVideoCount = \Illuminate\Support\Facades\Cache::remember('nav_video_count', 300, function () {
        return \Illuminate\Support\Facades\DB::table('videos')->where('status', 1)->whereNull('deleted_at')->count();
    });
    $kmComingSoonCount = \Illuminate\Support\Facades\Cache::remember('nav_comingsoon_count', 300, function () {
        return \Illuminate\Support\Facades\DB::table('entertainments')->where('status', 1)->whereNull('deleted_at')->whereNotNull('release_date')->where('release_date', '>', now())->count();
    });
    $kmPodcastCount = \Illuminate\Support\Facades\Cache::remember('nav_podcast_count', 300, function () use ($hasPodcastCol) {
        return $hasPodcastCol
            ? \Illuminate\Support\Facades\DB::table('entertainments')->where('type', 'tvshow')->where('is_podcast', 1)->where('status', 1)->whereNull('deleted_at')->count()
            : 0;
    });
    $kmMusicCount = \Illuminate\Support\Facades\Cache::remember('nav_music_count', 300, function () {
        return \Illuminate\Support\Facades\Schema::hasColumn('entertainments', 'is_music')
            ? \Illuminate\Support\Facades\DB::table('entertainments')->where('type', 'movie')->where('is_music', 1)->where('status', 1)->whereNull('deleted_at')->count()
            : 0;
    });
    // Documentaries = content in the Documentary genre (shown only when it has content).
    $kmDocGenreId = \Illuminate\Support\Facades\Cache::remember('nav_doc_genre_id', 300, function () {
        return \Illuminate\Support\Facades\DB::table('genres')->whereRaw('LOWER(name) = ?', ['documentary'])->value('id');
    });
    $kmDocCount = \Illuminate\Support\Facades\Cache::remember('nav_doc_count', 300, function () use ($kmDocGenreId) {
        if (! $kmDocGenreId) { return 0; }
        return \Illuminate\Support\Facades\DB::table('entertainment_gener_mapping as egm')
            ->join('entertainments as e', 'e.id', '=', 'egm.entertainment_id')
            ->where('egm.genre_id', $kmDocGenreId)->where('e.status', 1)->whereNull('e.deleted_at')->count();
    });
@endphp
<!-- Horizontal Menu Start -->
<nav id="navbar_main" class="offcanvas mobile-offcanvas nav navbar navbar-expand-xl hover-nav horizontal-nav py-xl-0">
  <div class="container-fluid p-lg-0">
    <div class="offcanvas-header">
      <div class="navbar-brand p-0">
        <!--Logo -->
        @include('frontend::components.partials.logo')

      </div>
      <button type="button" class="btn-close p-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <ul class="navbar-nav iq-nav-menu  list-unstyled" id="header-menu">
      <li class="nav-item">
        <a class="nav-link"  href="{{route('user.login')}}">
          <span class="item-name">{{__('frontend.home')}}</span>
        </a>
      </li>
      {{-- Categories listed directly across the nav (not inside a dropdown);
           each appears only when it has content. --}}
      @if(isenablemodule('movie') && $kmMovieCount >= 6)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('movies') }}">
          <span class="item-name">{{__('frontend.movies')}}</span>
        </a>
      </li>
      @endif
      @if($kmVeeMagCount >= 6)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('veemags') }}">
          <span class="item-name">{{__('frontend.veemags')}}</span>
        </a>
      </li>
      @endif
      @if(isenablemodule('tvshow') && $kmTvShowCount >= 6)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('tv-shows') }}">
          <span class="item-name">{{__('frontend.tvshows')}}</span>
        </a>
      </li>
      @endif
      @if($kmPodcastCount >= 6)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('media-series') }}">
          <span class="item-name">{{__('frontend.media_series')}}</span>
        </a>
      </li>
      @endif
      @if($kmMusicCount >= 6)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('music') }}">
          <span class="item-name">{{ __('frontend.music') }}</span>
        </a>
      </li>
      @endif
      @if($kmDocCount >= 6 && $kmDocGenreId)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('movies.genre', ['genre_id' => $kmDocGenreId]) }}">
          <span class="item-name">{{ __('frontend.documentaries') }}</span>
        </a>
      </li>
      @endif
      @if(isenablemodule('video') && $kmVideoCount >= 6)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('videos') }}">
          <span class="item-name">{{__('frontend.video')}}</span>
        </a>
      </li>
      @endif
      <!-- @if(isenablemodule('movie'))
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('movies') }}">
          <span class="item-name">{{__('frontend.movies')}}</span>
        </a>
      </li>
      @endif
      @if(isenablemodule('tvshow'))
@if($kmTvShowCount >= 6)
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('tv-shows') }}">
          <span class="item-name">{{__('frontend.tvshows')}}</span>
        </a>
      </li>
      @endif
@if($kmVeeMagCount >= 6)
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('veemags') }}">
          <span class="item-name">{{__('frontend.veemags')}}</span>
        </a>
      </li>
      @endif
      @endif
      @if(isenablemodule('video'))
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('videos') }}">
          <span class="item-name">{{__('frontend.video')}}</span>
        </a>
      </li>
      @endif -->
      @if($kmComingSoonCount >= 6)
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('comingsoon') }}">
          <span class="item-name">{{__('frontend.coming_soon')}}</span>
        </a>
      </li>
      @endif

    </ul>
  </div>
  <!-- container-fluid.// -->
</nav>
<!-- Horizontal Menu End -->
