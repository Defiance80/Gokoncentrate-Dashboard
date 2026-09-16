@extends('frontend::layouts.auth_layout')

@section('title', 'Become a Publisher — ' . GetSettingValue('app_name'))

@section('content')
    @include('frontend::components.partials.auth_backdrop')

    <div class="min-vh-100 gk-auth-stage">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-lg-6 col-md-9 col-11 align-self-center">
                    <div class="user-login-card card my-5">
                        <div class="text-center auth-heading">
                            @php
                                $logo = GetSettingValue('dark_logo') ? setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') : asset('img/logo/dark_logo.png');
                            @endphp
                            <a href="{{ url('/') }}" class="d-inline-block">
                                <img src="{{ $logo }}" class="img-fluid logo h-4 mb-4">
                            </a>
                            <h5>Apply to Publish</h5>
                            <p class="fs-14">Set up your studio account. You can start building right away — publications go live once approved.</p>
                        </div>

                        @if ($errors->any())
                            <div class="text-danger mb-2">
                                @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                            </div>
                        @endif

                        <form method="post" action="{{ route('studio.register.attempt') }}" class="requires-validation" novalidate>
                            @csrf
                            <div class="mb-3">
                                <div class="input-group mb-0">
                                    <span class="input-group-text"><i class="ph ph-user"></i></span>
                                    <input type="text" name="name" class="form-control" placeholder="Your name" value="{{ old('name') }}" required autofocus>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="input-group mb-0">
                                    <span class="input-group-text"><i class="ph ph-buildings"></i></span>
                                    <input type="text" name="company" class="form-control" placeholder="Publication / company (optional)" value="{{ old('company') }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="input-group mb-0">
                                        <span class="input-group-text"><i class="ph ph-envelope"></i></span>
                                        <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group mb-0">
                                        <span class="input-group-text"><i class="ph ph-phone"></i></span>
                                        <input type="text" name="phone" class="form-control" placeholder="Phone (optional)" value="{{ old('phone') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="input-group mb-0">
                                        <span class="input-group-text"><i class="ph ph-lock-key"></i></span>
                                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group mb-0">
                                        <span class="input-group-text"><i class="ph ph-lock-key"></i></span>
                                        <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password" required>
                                    </div>
                                </div>
                            </div>

                            <div class="full-button text-center">
                                <button type="submit" class="btn btn-primary w-100">Create studio account</button>
                                <p class="mt-2 mb-0 fw-normal">Already have an account?
                                    <a href="{{ route('studio.login') }}" class="ms-1 btn btn-link">Sign in</a>
                                </p>
                                <a href="{{ url('/') }}" class="d-block mt-4 btn btn-link">&larr; Back to {{ GetSettingValue('app_name') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
