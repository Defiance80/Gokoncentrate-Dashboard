@extends('backend.layouts.app')

@section('title')
    {{ __('mediaradar::mediaradar.settings') }}
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="ph ph-sliders me-2"></i>{{ __('mediaradar::mediaradar.settings') }}</h4>
        </div>
        <div class="card-body">
            {{ html()->form('POST', route('backend.media-radar-settings.store'))
                ->attribute('id', 'media-radar-settings-form')
                ->class('requires-validation')
                ->attribute('novalidate', 'novalidate')
                ->open() }}
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">{{ __('mediaradar::mediaradar.section_approval') }}</h5>

                    <div class="form-group mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="auto_approve" name="auto_approve" value="1"
                                {{ $settings->auto_approve ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_approve">
                                {{ __('mediaradar::mediaradar.auto_approval') }}
                            </label>
                        </div>
                        <small class="text-muted">{{ __('mediaradar::mediaradar.auto_approval_help') }}</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('mediaradar::mediaradar.lbl_auto_approve_min_score') }}</label>
                        {{ html()->number('auto_approve_min_score', $settings->auto_approve_min_score)
                            ->class('form-control')->attribute('min', 0)->attribute('max', 100)->required() }}
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="auto_publish_after_approval"
                                name="auto_publish_after_approval" value="1"
                                {{ $settings->auto_publish_after_approval ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_publish_after_approval">
                                {{ __('mediaradar::mediaradar.lbl_auto_publish') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="ai_enabled" name="ai_enabled" value="1"
                                {{ $settings->ai_enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="ai_enabled">
                                {{ __('mediaradar::mediaradar.lbl_ai_enabled') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">{{ __('mediaradar::mediaradar.section_cover_art') }}</h5>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('mediaradar::mediaradar.lbl_cover_art_mode') }}</label>
                        {{ html()->select('cover_art_mode', $coverArtModes, $settings->cover_art_mode)->class('form-control select2') }}
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('mediaradar::mediaradar.lbl_cover_crop_ratio') }}</label>
                        {{ html()->text('cover_crop_ratio', $settings->cover_crop_ratio)->class('form-control')->placeholder('2:3')->required() }}
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('mediaradar::mediaradar.lbl_expiration_days') }}</label>
                        {{ html()->number('candidate_expiration_days', $settings->candidate_expiration_days)
                            ->class('form-control')->attribute('min', 1)->required() }}
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">{{ __('mediaradar::mediaradar.section_publishing') }}</h5>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('mediaradar::mediaradar.lbl_access') }}</label>
                        {{ html()->select('default_movie_access', [
                            'free' => 'Free',
                            'paid' => 'Paid',
                            'pay-per-view' => 'Pay per view',
                        ], $settings->default_movie_access)->class('form-control select2') }}
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('mediaradar::mediaradar.lbl_plan') }}</label>
                        {{ html()->select('default_plan_id', ['' => '-'] + $plans->toArray(), $settings->default_plan_id)->class('form-control select2') }}
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="default_publish_status"
                                name="default_publish_status" value="1"
                                {{ $settings->default_publish_status ? 'checked' : '' }}>
                            <label class="form-check-label" for="default_publish_status">
                                {{ __('mediaradar::mediaradar.lbl_default_publish_status') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="default_is_restricted"
                                name="default_is_restricted" value="1"
                                {{ $settings->default_is_restricted ? 'checked' : '' }}>
                            <label class="form-check-label" for="default_is_restricted">
                                {{ __('mediaradar::mediaradar.lbl_restricted') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">{{ __('mediaradar::mediaradar.section_platforms') }}</h5>

                    <div class="form-group mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="enabled" name="enabled" value="1"
                                {{ $settings->enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="enabled">{{ __('mediaradar::mediaradar.lbl_enabled') }}</label>
                        </div>
                    </div>

                    @foreach (['youtube' => __('mediaradar::mediaradar.lbl_youtube_enabled'), 'vimeo' => __('mediaradar::mediaradar.lbl_vimeo_enabled')] as $slug => $label)
                        @php $health = collect($providerHealth)->firstWhere('slug', $slug); @endphp
                        <div class="form-group mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="{{ $slug }}_enabled"
                                    name="{{ $slug }}_enabled" value="1"
                                    {{ $settings->{$slug.'_enabled'} ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $slug }}_enabled">{{ $label }}</label>
                            </div>
                            @if ($health)
                                <small class="text-muted d-block">
                                    {{ __('mediaradar::mediaradar.'.$health['status']) }}
                                    @if ($health['last_success_at'])
                                        &middot; {{ __('mediaradar::mediaradar.last_success') }}: {{ $health['last_success_at'] }}
                                    @endif
                                </small>
                                @if (! empty($health['last_error']))
                                    <small class="text-danger d-block">{{ \Illuminate\Support\Str::limit($health['last_error'], 160) }}</small>
                                @endif
                            @endif
                        </div>
                    @endforeach

                    <div class="alert alert-secondary small mb-0">
                        {{ __('mediaradar::mediaradar.credentials_note') }}
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
            </div>

            {{ html()->form()->close() }}
        </div>
    </div>
@endsection
