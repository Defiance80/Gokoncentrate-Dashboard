@extends('frontend::layouts.auth_layout')

@section('title', 'Publisher Studio — ' . GetSettingValue('app_name'))

@section('content')
    @include('frontend::components.partials.auth_backdrop')

    <div class="min-vh-100 gk-auth-stage">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-lg-5 col-md-8 col-11 align-self-center">
                    <div class="user-login-card card my-5">
                        <div class="text-center auth-heading">
                            @php
                                $logo = GetSettingValue('dark_logo') ? setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') : asset('img/logo/dark_logo.png');
                            @endphp
                            <a href="{{ url('/') }}" class="d-inline-block">
                                <img src="{{ $logo }}" class="img-fluid logo h-4 mb-4">
                            </a>
                            <h5>Publisher Studio</h5>
                            <p class="fs-14">Sign in to create and manage your publications.</p>
                        </div>

                        @if ($errors->any())
                            <p class="text-danger">{{ $errors->first() }}</p>
                        @endif
                        @if (session('status'))
                            <p class="text-success">{{ session('status') }}</p>
                        @endif

                        <form method="post" action="{{ route('studio.login.attempt') }}" class="requires-validation" novalidate>
                            @csrf
                            <div class="mb-3">
                                <div class="input-group mb-0">
                                    <span class="input-group-text"><i class="ph ph-envelope"></i></span>
                                    <input type="email" name="email" id="email" class="form-control"
                                        placeholder="Enter email" value="{{ old('email') }}" required autofocus>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="input-group mb-0">
                                    <span class="input-group-text"><i class="ph ph-lock-key"></i></span>
                                    <input type="password" name="password" id="password" class="form-control"
                                        placeholder="Enter password" required>
                                    <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
                                        <i class="ph ph-eye-slash" id="toggleIcon"></i>
                                    </span>
                                </div>
                            </div>

                            <label class="d-flex align-items-center mb-3">
                                <input class="form-check-input m-0 me-2" type="checkbox" name="remember" value="1">
                                Keep me signed in
                            </label>

                            <div class="full-button text-center">
                                <button type="submit" class="btn btn-primary w-100">Sign In</button>
                                <p class="mt-2 mb-0 fw-normal">New publisher?
                                    <a href="{{ route('studio.register') }}" class="ms-1 btn btn-link">Apply now</a>
                                </p>
                                <a href="{{ url('/') }}" class="d-block mt-4 btn btn-link">&larr; Back to {{ GetSettingValue('app_name') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var t = document.getElementById('togglePassword'),
                p = document.getElementById('password'),
                i = document.getElementById('toggleIcon');
            if (t && p && i) t.addEventListener('click', function () {
                var hidden = p.type === 'password';
                p.type = hidden ? 'text' : 'password';
                i.classList.toggle('ph-eye', hidden);
                i.classList.toggle('ph-eye-slash', !hidden);
            });
        });
    </script>
@endsection
