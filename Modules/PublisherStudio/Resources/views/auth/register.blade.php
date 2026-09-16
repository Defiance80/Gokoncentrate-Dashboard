@extends('publisherstudio::layouts.guest')
@section('title','Become a Publisher')
@section('content')
<div class="gk-card">
  <h1 style="margin:0 0 .3rem;font-size:1.25rem">Apply to publish</h1>
  <p class="gk-muted" style="margin:0 0 1.2rem;font-size:.9rem">Set up your studio account. You can start building right away — publications go live once approved.</p>

  @if($errors->any())
    <div class="gk-alert err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
  @endif

  <form method="post" action="{{ route('studio.register.attempt') }}">
    @csrf
    <div class="gk-field">
      <label class="gk-label" for="name">Your name</label>
      <input class="gk-input" type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
    </div>
    <div class="gk-field">
      <label class="gk-label" for="company">Publication / company <span class="gk-muted">(optional)</span></label>
      <input class="gk-input" type="text" id="company" name="company" value="{{ old('company') }}">
    </div>
    <div class="gk-grid cols-2">
      <div class="gk-field">
        <label class="gk-label" for="email">Email</label>
        <input class="gk-input" type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
      </div>
      <div class="gk-field">
        <label class="gk-label" for="phone">Phone <span class="gk-muted">(optional)</span></label>
        <input class="gk-input" type="text" id="phone" name="phone" value="{{ old('phone') }}">
      </div>
    </div>
    <div class="gk-grid cols-2">
      <div class="gk-field">
        <label class="gk-label" for="password">Password</label>
        <input class="gk-input" type="password" id="password" name="password" required autocomplete="new-password">
      </div>
      <div class="gk-field">
        <label class="gk-label" for="password_confirmation">Confirm password</label>
        <input class="gk-input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
      </div>
    </div>
    <button class="gk-btn block" type="submit">Create studio account</button>
  </form>
</div>
<div class="auth-foot">Already have an account? <a href="{{ route('studio.login') }}">Sign in</a></div>
@endsection
