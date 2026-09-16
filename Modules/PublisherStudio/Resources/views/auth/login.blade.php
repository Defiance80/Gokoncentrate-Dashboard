@extends('publisherstudio::layouts.guest')
@section('title','Publisher Sign In')
@section('content')
<div class="gk-card">
  <h1 style="margin:0 0 .3rem;font-size:1.25rem">Sign in to your studio</h1>
  <p class="gk-muted" style="margin:0 0 1.2rem;font-size:.9rem">Create and manage your VeeMags, podcasts and short films.</p>

  @if($errors->any())<div class="gk-alert err">{{ $errors->first() }}</div>@endif
  @if(session('status'))<div class="gk-alert ok">{{ session('status') }}</div>@endif

  <form method="post" action="{{ route('studio.login.attempt') }}">
    @csrf
    <div class="gk-field">
      <label class="gk-label" for="email">Email</label>
      <input class="gk-input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
    </div>
    <div class="gk-field">
      <label class="gk-label" for="password">Password</label>
      <input class="gk-input" type="password" id="password" name="password" required autocomplete="current-password">
    </div>
    <label style="display:flex;gap:.5rem;align-items:center;color:var(--mut);font-size:.85rem;margin-bottom:1.1rem">
      <input type="checkbox" name="remember" value="1"> Keep me signed in
    </label>
    <button class="gk-btn block" type="submit">Sign In</button>
  </form>
</div>
<div class="auth-foot">New publisher? <a href="{{ route('studio.register') }}">Apply for a studio account</a></div>
@endsection
