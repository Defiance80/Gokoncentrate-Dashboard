<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark"
    dir="{{ session()->has('dir') ? session()->get('dir') : 'ltr' }}"
    data-bs-theme-color="{{ getCustomizationSetting('theme_color') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ url('/') }}">
    <title>@yield('title', 'Publisher Studio') — {{ GetSettingValue('app_name') }}</title>

    @php
        $faviconUrl = GetSettingValue('favicon') ? setBaseUrlWithFileName(GetSettingValue('favicon'),'image','logos') : asset('img/logo/favicon.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">

    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('modules/frontend/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customizer.css') }}">
    <link rel="stylesheet" href="{{ asset('iconly/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/fill/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/bold/style.css') }}">
    @include('frontend::components.partials.head.plugins')

    <style>
        .studio-nav{border-bottom:1px solid var(--bs-border-color);background:var(--bs-body-bg);position:sticky;top:0;z-index:1020}
        .studio-nav .nav-link{color:var(--bs-secondary-color);font-weight:500;padding:.4rem .9rem;border-radius:.5rem}
        .studio-nav .nav-link.active,.studio-nav .nav-link:hover{color:var(--bs-body-color);background:var(--bs-tertiary-bg)}
        .studio-logo{height:32px;width:auto}
        .studio-who{line-height:1.15}
        .studio-who small{color:var(--bs-secondary-color)}
        .stat-tile .display-6{font-weight:700}
        main.studio-main{padding:2rem 0 4rem}
    </style>
    @stack('after-styles')
</head>

<body class="d-flex flex-column min-vh-100">
    @php $publisher = auth('publisher')->user(); $active = $active ?? '';
        $navLogo = GetSettingValue('dark_logo') ? setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') : asset('img/logo/dark_logo.png');
    @endphp

    <header class="studio-nav">
        <div class="container">
            <nav class="navbar navbar-expand-lg py-2">
                <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('studio.dashboard') }}">
                    <img src="{{ $navLogo }}" alt="{{ GetSettingValue('app_name') }}" class="studio-logo">
                    <span class="badge rounded-pill text-bg-primary">Studio</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studioNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="studioNav">
                    <ul class="navbar-nav ms-lg-3 me-auto mb-2 mb-lg-0 gap-1">
                        <li class="nav-item"><a class="nav-link {{ $active==='dashboard'?'active':'' }}" href="{{ route('studio.dashboard') }}">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link {{ $active==='submissions'?'active':'' }}" href="{{ route('studio.submissions.index') }}">My Publications</a></li>
                        <li class="nav-item"><a class="nav-link {{ $active==='create'?'active':'' }}" href="{{ route('studio.submissions.create') }}">New</a></li>
                    </ul>
                    <div class="d-flex align-items-center gap-3">
                        <div class="studio-who text-end d-none d-sm-block">
                            <div class="fw-semibold">{{ $publisher->name ?? '' }}</div>
                            <small>{{ ($publisher->status ?? '')==='approved' ? 'Approved publisher' : 'Account under review' }}</small>
                        </div>
                        <form method="post" action="{{ route('studio.logout') }}" class="m-0">@csrf
                            <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
                        </form>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <main class="studio-main flex-fill">
        <div class="container">
            @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @yield('content')
        </div>
    </main>

    @include('frontend::components.partials.scripts.plugins')
    <script src="{{ mix('modules/frontend/script.js') }}" defer></script>
    @stack('after-scripts')
</body>
</html>
