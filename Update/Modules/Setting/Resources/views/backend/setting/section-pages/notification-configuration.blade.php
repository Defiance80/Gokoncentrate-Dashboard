@extends('setting::backend.setting.index')

@section('title')
    {{ __('setting_sidebar.lbl_notification_configuration') }}
@endsection

@section('settings-content')
    {{ html()->form('POST', route('backend.setting.store'))->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->class('requires-validation')->attribute('novalidate', 'novalidate')->attribute('enctype', 'multipart/form-data')->open() }}
    @csrf
    <input type="hidden" name="setting_tab" value="notification_configuration">

    <div class="card mb-0">
        <div class="card-header p-0">
            <h3 class="mb-3"><i class="fa-solid fa-bell"></i> {{ __('setting_sidebar.lbl_notification_configuration') }}</h3>
        </div>
        <div class="card-body px-0">
            <div class="row gy-3">

                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        {{ html()->label(__('messages.lbl_expiry_plan') . ' <span class="text-danger">*</span>') }}
                        <i class="ph ph-info text-primary cursor-pointer" 
                           data-bs-toggle="tooltip" 
                           data-bs-placement="top"
                           title="{{ __('messages.expiry_plan_tooltip') }}"></i>
                    </div>
                    {{ html()->number('expiry_plan')->class('form-control')->placeholder(__('messages.lbl_expiry_plan') . ' ' . __('messages.days'))->value(old('expiry_plan', $settings['expiry_plan'] ?? ''))->required() }}
                    @error('expiry_plan')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="expiry-plan-error">{{ __('messages.expiry_plan_required') }}</div>
                </div>

                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        {{ html()->label(__('messages.lbl_upcoming') . ' <span class="text-danger">*</span>') }}
                        <i class="ph ph-info text-primary cursor-pointer" 
                           data-bs-toggle="tooltip" 
                           data-bs-placement="top"
                           title="{{ __('messages.upcoming_tooltip') }}"></i>
                    </div>
                    {{ html()->number('upcoming')->class('form-control')->placeholder(__('messages.lbl_upcoming') . ' ' . __('messages.days'))->value(old('upcoming', $settings['upcoming'] ?? ''))->required() }}
                    @error('upcoming')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="upcoming-error">{{ __('messages.upcoming_required') }}</div>
                </div>

                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        {{ html()->label(__('messages.lbl_continue_watch') . ' <span class="text-danger">*</span>')  }}
                        <i class="ph ph-info text-primary cursor-pointer" 
                           data-bs-toggle="tooltip" 
                           data-bs-placement="top"
                           title="{{ __('messages.continue_watch_tooltip') }}"></i>
                    </div>
                    {{ html()->number('continue_watch')->class('form-control')->placeholder(__('messages.lbl_continue_watch') . ' ' . __('messages.days'))->value(old('continue_watch', $settings['continue_watch'] ?? ''))->required() }}
                    @error('continue_watch')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="continue-watch-error">{{ __('messages.continue_watch_required') }}</div>
                </div>

            </div>
        </div>
        <div class="text-end">
            {{ html()->button(__('messages.save'))->type('submit')->attribute('id', 'submit-button')->class('btn btn-primary')->id('submit-button') }}
        </div>
    </div>
    </form>
@endsection

@push('after-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush
