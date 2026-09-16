<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Publisher Studio') — GoKoncentrate</title>
  @include('publisherstudio::layouts._theme')
  <style>
    .auth-shell{min-height:100vh;min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:32px 16px}
    .auth-card{width:100%;max-width:440px}
    .auth-head{text-align:center;margin-bottom:1.6rem}
    .auth-head h1{font-size:1.5rem;margin:.9rem 0 .3rem;letter-spacing:-.02em}
    .auth-foot{text-align:center;margin-top:1.2rem;color:var(--mut);font-size:.9rem}
  </style>
</head>
<body>
  <div class="auth-shell">
    <div class="auth-card">
      <div class="auth-head">
        <div class="gk-brand" style="justify-content:center">
          <span class="dot"></span>
          <div>GoKoncentrate<small>Publisher Studio</small></div>
        </div>
      </div>
      @yield('content')
    </div>
  </div>
</body>
</html>
