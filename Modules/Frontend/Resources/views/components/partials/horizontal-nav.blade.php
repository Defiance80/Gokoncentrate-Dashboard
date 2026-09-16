@php
    $kmTvShowCount = \Illuminate\Support\Facades\Cache::remember('nav_tvshow_count', 300, function () {
        return \Illuminate\Support\Facades\DB::table('entertainments')->where('type', 'tvshow')->where('is_veemag', 0)->where('status', 1)->whereNull('deleted_at')->count();
    });
    $kmVeeMagCount = \Illuminate\Support\Facades\Cache::remember('nav_veemag_count', 300, function () {
        return \Illuminate\Support\Facades\DB::table('entertainments')->where('type', 'tvshow')->where('is_veemag', 1)->where('status', 1)->whereNull('deleted_at')->count();
    });
    $kmPodcastCount = \Illuminate\Support\Facades\Cache::remember('nav_podcast_count', 300, function () {
        return \Illuminate\Support\Facades\Schema::hasColumn('entertainments', 'is_podcast')
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
      <li class="nav-item">
        <a class="nav-link" href="#">
          <span class="item-name">{{__('frontend.all_content')}}</span>
        </a>
        <ul class="sub-menu list-unstyled">
          @if(isenablemodule('movie'))
          <li class="nav-item">
            <a class="nav-link"  href="{{ route('movies') }}">
              <span class="item-name">{{__('frontend.movies')}}</span>
            </a>
          </li>
          @endif
          @if(isenablemodule('tvshow'))
          <li class="nav-item">
            <a class="nav-link"  href="{{ route('tv-shows') }}">
              <span class="item-name">{{__('frontend.tvshows')}}</span>
            </a>
          </li>
          @endif
          @if(isenablemodule('video'))
          <li class="nav-item">
            <a class="nav-link"  href="{{ route('videos') }}">
              <span class="item-name">{{__('frontend.video')}}</span>
            </a>
          </li>
          @endif
        </ul>
      </li>
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
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('comingsoon') }}">
          <span class="item-name">{{__('frontend.coming_soon')}}</span>
        </a>
      </li>
      @if($kmPodcastCount > 0)
      <li class="nav-item">
        <a class="nav-link"  href="{{ route('media-series') }}">
          <span class="item-name">{{__('frontend.media_series')}}</span>
        </a>
      </li>
      @endif

    </ul>
  </div>
  <!-- container-fluid.// -->
</nav>
<!-- Horizontal Menu End -->
