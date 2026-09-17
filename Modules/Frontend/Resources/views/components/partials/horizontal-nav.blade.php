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
      @if(isenablemodule('movie') && $kmMovieCount > 0)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('movies') }}">
          <span class="item-name">{{__('frontend.movies')}}</span>
        </a>
      </li>
      @endif
      @if($kmVeeMagCount > 0)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('veemags') }}">
          <span class="item-name">{{__('frontend.veemags')}}</span>
        </a>
      </li>
      @endif
      @if(isenablemodule('tvshow') && $kmTvShowCount > 0)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('tv-shows') }}">
          <span class="item-name">{{__('frontend.tvshows')}}</span>
        </a>
      </li>
      @endif
      @if($kmPodcastCount > 0)
      <li class="nav-item">
        <a class="nav-link" href="{{ route('media-series') }}">
          <span class="item-name">{{__('frontend.media_series')}}</span>
        </a>
      </li>
      @endif
      @if(isenablemodule('video') && $kmVideoCount > 0)
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
@if($kmTvShowCount > 0)
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('tv-shows') }}">
          <span class="item-name">{{__('frontend.tvshows')}}</span>
        </a>
      </li>
      @endif
@if($kmVeeMagCount > 0)
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
      @if($kmComingSoonCount > 0)
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
