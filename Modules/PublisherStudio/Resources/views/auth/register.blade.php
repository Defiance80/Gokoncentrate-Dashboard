@extends('frontend::layouts.auth_layout')

@section('title', 'Become a Publisher — ' . GetSettingValue('app_name'))

@php
    $formats = [
        'veemag'      => 'VeeMags (video magazines)',
        'podcast'     => 'Podcasts (episodic series)',
        'short_film'  => 'Short Films',
        'music_video' => 'Music Videos',
    ];
    $oldFormats = old('formats', []);
@endphp

@section('content')
    @include('frontend::components.partials.auth_backdrop')

    <div class="min-vh-100 gk-auth-stage">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-lg-8 col-md-10 col-11 align-self-center">
                    <div class="user-login-card card my-5">
                        <div class="text-center auth-heading">
                            @php
                                $logo = GetSettingValue('dark_logo') ? setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') : asset('img/logo/dark_logo.png');
                            @endphp
                            <a href="{{ url('/') }}" class="d-inline-block">
                                <img src="{{ $logo }}" class="img-fluid logo h-4 mb-4">
                            </a>
                            <h5>Apply to Publish</h5>
                            <p class="fs-14">Tell us about your work so we can qualify your studio. You can start building right away — publications go live once approved.</p>
                        </div>

                        @if ($errors->any())
                            <div class="text-danger mb-3">
                                @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                            </div>
                        @endif

                        <form method="post" action="{{ route('studio.register.attempt') }}" class="requires-validation" novalidate>
                            @csrf

                            <h6 class="text-uppercase text-secondary fw-semibold mt-2 mb-3" style="letter-spacing:.08em">Your account</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Your name</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autofocus>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Publication / company</label>
                                    <input type="text" name="company" class="form-control" value="{{ old('company') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone <span class="text-secondary">(optional)</span></label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm password</label>
                                    <input type="password" name="password_confirmation" class="form-control" required>
                                </div>
                            </div>

                            <h6 class="text-uppercase text-secondary fw-semibold mt-3 mb-3" style="letter-spacing:.08em">About your publication</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Website / portfolio <span class="text-secondary">(optional)</span></label>
                                    <input type="text" name="website" class="form-control" value="{{ old('website') }}" placeholder="https://…">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Primary content focus</label>
                                    <input type="text" name="content_focus" class="form-control" value="{{ old('content_focus') }}" placeholder="e.g. Music, Culture, Sports, Business" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label d-block">What do you plan to publish? <span class="text-secondary">(select all that apply)</span></label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($formats as $key => $label)
                                        <input type="checkbox" class="btn-check" name="formats[]" id="fmt_{{ $key }}" value="{{ $key }}" {{ in_array($key, $oldFormats) ? 'checked' : '' }} autocomplete="off">
                                        <label class="btn btn-outline-primary btn-sm" for="fmt_{{ $key }}">{{ $label }}</label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Publishing cadence</label>
                                    <select name="cadence" class="form-select" required>
                                        @php $cad = old('cadence'); @endphp
                                        <option value="" {{ $cad?'':'selected' }} disabled>Select…</option>
                                        @foreach (['Weekly','Bi-weekly','Monthly','Quarterly','Per issue / ad-hoc'] as $c)
                                            <option value="{{ $c }}" {{ $cad===$c?'selected':'' }}>{{ $c }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Audience size <span class="text-secondary">(optional)</span></label>
                                    <select name="audience_size" class="form-select">
                                        @php $aud = old('audience_size'); @endphp
                                        <option value="" {{ $aud?'':'selected' }}>Select…</option>
                                        @foreach (['Just starting','1K-10K','10K-100K','100K-1M','1M+'] as $a)
                                            <option value="{{ $a }}" {{ $aud===$a?'selected':'' }}>{{ $a }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Primary market / region <span class="text-secondary">(optional)</span></label>
                                    <input type="text" name="region" class="form-control" value="{{ old('region') }}" placeholder="e.g. Southern California">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Instagram <span class="text-secondary">(optional)</span></label>
                                    <input type="text" name="social_instagram" class="form-control" value="{{ old('social_instagram') }}" placeholder="@handle or URL">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">YouTube <span class="text-secondary">(optional)</span></label>
                                    <input type="text" name="social_youtube" class="form-control" value="{{ old('social_youtube') }}" placeholder="channel URL">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">TikTok <span class="text-secondary">(optional)</span></label>
                                    <input type="text" name="social_tiktok" class="form-control" value="{{ old('social_tiktok') }}" placeholder="@handle or URL">
                                </div>
                            </div>

                            <div class="row align-items-end">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label d-block">Do you have existing content?</label>
                                    @php $hc = old('has_content'); @endphp
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="has_content" id="hc_yes" value="yes" {{ $hc==='yes'?'checked':'' }}>
                                        <label class="btn btn-outline-primary btn-sm" for="hc_yes">Yes</label>
                                        <input type="radio" class="btn-check" name="has_content" id="hc_no" value="no" {{ $hc==='no'?'checked':'' }}>
                                        <label class="btn btn-outline-primary btn-sm" for="hc_no">No</label>
                                    </div>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Sample link <span class="text-secondary">(optional)</span></label>
                                    <input type="text" name="sample_url" class="form-control" value="{{ old('sample_url') }}" placeholder="Link to a reel, episode, issue or portfolio">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Relevant experience <span class="text-secondary">(optional)</span></label>
                                <textarea name="experience" class="form-control" rows="2" placeholder="Production, editing, publishing, print — anything relevant.">{{ old('experience') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">What do you want to publish on GoKoncentrate?</label>
                                <textarea name="pitch" class="form-control" rows="3" required placeholder="Tell us about the publication, the audience, and how often you'll release.">{{ old('pitch') }}</textarea>
                            </div>

                            <label class="d-flex align-items-start gap-2 mb-3">
                                <input class="form-check-input m-0 mt-1" type="checkbox" name="agree_terms" value="1" required>
                                <span class="small text-secondary">I confirm the work I publish is my own or licensed, and I agree to GoKoncentrate's publisher terms and content guidelines.</span>
                            </label>

                            <div class="full-button text-center">
                                <button type="submit" class="btn btn-primary w-100">Submit application</button>
                                <p class="mt-2 mb-0 fw-normal">Already have an account?
                                    <a href="{{ route('studio.login') }}" class="ms-1 btn btn-link">Sign in</a>
                                </p>
                                <a href="{{ url('/') }}" class="d-block mt-3 btn btn-link">&larr; Back to {{ GetSettingValue('app_name') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
