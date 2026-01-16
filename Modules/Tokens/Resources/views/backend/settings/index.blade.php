@extends('backend.layouts.app')

@section('title') {{ __('tokens::tokens.settings_title') }} @endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="mb-0"><i class="ph ph-gear me-2"></i>{{ __('tokens::tokens.settings_title') }}</h4>
    </div>
    <div class="card-body">
        {{ html()->form('POST', route('backend.token-settings.store'))
            ->attribute('data-toggle', 'validator')
            ->attribute('id', 'token-settings-form')
            ->class('requires-validation')
            ->attribute('novalidate', 'novalidate')
            ->open()
        }}
        @csrf

        <div class="row">
            <!-- Display Settings -->
            <div class="col-md-6">
                <h5 class="border-bottom pb-2 mb-3">{{ __('tokens::tokens.section_display') }}</h5>

                <div class="form-group mb-3">
                    <label class="form-label">{{ __('tokens::tokens.lbl_token_label') }} <span class="text-danger">*</span></label>
                    {{ html()->text('token_label')
                        ->class('form-control')
                        ->value($settings->token_label ?? 'KT')
                        ->maxlength(10)
                        ->required() }}
                    <small class="text-muted">{{ __('tokens::tokens.help_token_label') }}</small>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">{{ __('tokens::tokens.lbl_token_name') }} <span class="text-danger">*</span></label>
                    {{ html()->text('token_name')
                        ->class('form-control')
                        ->value($settings->token_name ?? 'GoKoncentrate Tokens')
                        ->maxlength(100)
                        ->required() }}
                </div>
            </div>

            <!-- Control Settings -->
            <div class="col-md-6">
                <h5 class="border-bottom pb-2 mb-3">{{ __('tokens::tokens.section_control') }}</h5>

                <div class="form-group mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="global_enabled" name="global_enabled" value="1"
                            {{ $settings->global_enabled ? 'checked' : '' }}>
                        <label class="form-check-label" for="global_enabled">{{ __('tokens::tokens.lbl_global_enabled') }}</label>
                    </div>
                    <small class="text-muted">{{ __('tokens::tokens.help_global_enabled') }}</small>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">{{ __('tokens::tokens.lbl_daily_cap') }}</label>
                    {{ html()->number('daily_cap_cents')
                        ->class('form-control')
                        ->value($settings->daily_cap_cents)
                        ->attribute('min', 0)
                        ->placeholder('Leave empty for no cap') }}
                    <small class="text-muted">{{ __('tokens::tokens.help_daily_cap') }}</small>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">{{ __('tokens::tokens.lbl_repeat_cooldown') }}</label>
                    {{ html()->number('repeat_cooldown_seconds')
                        ->class('form-control')
                        ->value($settings->repeat_cooldown_seconds ?? 0)
                        ->attribute('min', 0) }}
                    <small class="text-muted">{{ __('tokens::tokens.help_repeat_cooldown') }}</small>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Valuation Settings (Super Admin Only) -->
            <div class="col-md-6">
                <h5 class="border-bottom pb-2 mb-3">
                    {{ __('tokens::tokens.section_valuation') }}
                    @if(!$isSuperAdmin)
                        <span class="badge bg-secondary">{{ __('tokens::tokens.super_admin_only') }}</span>
                    @endif
                </h5>

                <div class="form-group mb-3">
                    <label class="form-label">{{ __('tokens::tokens.lbl_usd_cents_per_token') }}</label>
                    {{ html()->number('token_usd_cents_per_token')
                        ->class('form-control')
                        ->value($settings->token_usd_cents_per_token ?? 100)
                        ->attribute('min', 1)
                        ->disabled(!$isSuperAdmin) }}
                    <small class="text-muted">{{ __('tokens::tokens.help_usd_cents') }}</small>
                </div>
            </div>

            <!-- Earning Settings (Super Admin Only) -->
            <div class="col-md-6">
                <h5 class="border-bottom pb-2 mb-3">
                    {{ __('tokens::tokens.section_earning') }}
                    @if(!$isSuperAdmin)
                        <span class="badge bg-secondary">{{ __('tokens::tokens.super_admin_only') }}</span>
                    @endif
                </h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('tokens::tokens.lbl_earn_cents') }}</label>
                            {{ html()->number('earn_cents')
                                ->class('form-control')
                                ->value($settings->earn_cents ?? 1)
                                ->attribute('min', 0)
                                ->disabled(!$isSuperAdmin) }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('tokens::tokens.lbl_earn_seconds') }}</label>
                            {{ html()->number('earn_seconds')
                                ->class('form-control')
                                ->value($settings->earn_seconds ?? 10)
                                ->attribute('min', 1)
                                ->disabled(!$isSuperAdmin) }}
                        </div>
                    </div>
                </div>
                <small class="text-muted">{{ __('tokens::tokens.help_earn_rate') }}</small>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Eligible Content -->
            <div class="col-md-12">
                <h5 class="border-bottom pb-2 mb-3">{{ __('tokens::tokens.lbl_eligible_content') }}</h5>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="flag_free_video" name="flag_free_video" value="1"
                                {{ ($settings->eligible_content_flags['free_video'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="flag_free_video">{{ __('tokens::tokens.flag_free_video') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="flag_free_magazine" name="flag_free_magazine" value="1"
                                {{ ($settings->eligible_content_flags['free_magazine'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="flag_free_magazine">{{ __('tokens::tokens.flag_free_magazine') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="flag_focus_mode" name="flag_focus_mode" value="1"
                                {{ ($settings->eligible_content_flags['focus_mode'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="flag_focus_mode">{{ __('tokens::tokens.flag_focus_mode') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group text-end mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="ph ph-floppy-disk me-1"></i>{{ __('messages.save') }}
            </button>
        </div>

        {{ html()->form()->close() }}
    </div>
</div>
@endsection

@push('after-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('token-settings-form');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                Swal.fire({
                    title: '{{ __("messages.success") }}',
                    text: data.message,
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 2000
                });
            } else {
                Swal.fire({
                    title: '{{ __("messages.error") }}',
                    text: data.message || '{{ __("messages.something_wrong") }}',
                    icon: 'error'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: '{{ __("messages.error") }}',
                text: '{{ __("messages.something_wrong") }}',
                icon: 'error'
            });
        });
    });
});
</script>
@endpush
