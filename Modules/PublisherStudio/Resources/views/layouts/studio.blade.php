<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Studio') — GoKoncentrate Publisher Studio</title>
  @include('publisherstudio::layouts._theme')
  <style>
    .topbar{position:sticky;top:0;z-index:20;background:rgba(11,10,15,.82);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}
    .topbar-in{display:flex;align-items:center;gap:1rem;padding:.85rem 0}
    .topbar nav{display:flex;gap:.3rem;margin-left:1rem;flex-wrap:wrap}
    .topbar nav a{color:var(--mut);font-weight:600;font-size:.9rem;padding:.4rem .8rem;border-radius:2rem}
    .topbar nav a.active,.topbar nav a:hover{color:var(--txt);background:rgba(255,255,255,.06)}
    .spacer{flex:1}
    .who{font-size:.85rem;color:var(--mut);text-align:right;line-height:1.2}
    .who b{color:var(--txt);display:block}
    main{padding:2rem 0 4rem}
    .page-head{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1.4rem;flex-wrap:wrap}
    .page-head h1{margin:0;font-size:1.6rem;letter-spacing:-.02em}
    .page-head p{margin:.3rem 0 0;color:var(--mut)}
  </style>
</head>
<body>
  @php $publisher = auth('publisher')->user(); $active = $active ?? ''; @endphp
  <header class="topbar">
    <div class="gk-wrap topbar-in">
      <a href="{{ route('studio.dashboard') }}" class="gk-brand"><span class="dot"></span><div>GoKoncentrate<small>Publisher Studio</small></div></a>
      <nav>
        <a href="{{ route('studio.dashboard') }}" class="{{ $active==='dashboard'?'active':'' }}">Dashboard</a>
        <a href="{{ route('studio.submissions.index') }}" class="{{ $active==='submissions'?'active':'' }}">My Publications</a>
        <a href="{{ route('studio.submissions.create') }}" class="{{ $active==='create'?'active':'' }}">New</a>
      </nav>
      <div class="spacer"></div>
      <div class="who"><b>{{ $publisher->name ?? '' }}</b>{{ $publisher->status==='approved' ? 'Approved publisher' : 'Account under review' }}</div>
      <form method="post" action="{{ route('studio.logout') }}">@csrf<button class="gk-btn ghost sm" type="submit">Sign out</button></form>
    </div>
  </header>
  <main>
    <div class="gk-wrap">
      @if(session('status'))<div class="gk-alert ok">{{ session('status') }}</div>@endif
      @if(session('error'))<div class="gk-alert err">{{ session('error') }}</div>@endif
      @yield('content')
    </div>
  </main>
</body>
</html>
