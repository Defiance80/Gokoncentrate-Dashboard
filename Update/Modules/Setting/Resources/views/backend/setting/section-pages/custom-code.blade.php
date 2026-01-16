@extends('setting::backend.setting.index')
@section('title')
    {{ __('setting_sidebar.lbl_custom_code') }}
@endsection

@section('settings-content')
    <form method="POST" action="{{ route('backend.setting.store') }}" id="form-submit" class="requires-validation" novalidate
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="setting_tab" value="custom_code">
        <div>
            <div class="d-flex justify-content-between align-items-center card-title">
                <h3 class="mb-3"><i class="fa-solid fa-file-code"></i> {{ __('setting_sidebar.lbl_custom_code') }}</h3>
            </div>
        </div>
        <div class="form-group">

            <label for="custom_css_block" class="form-label">{{ __('setting_custom_code.lbl_css_name') }} </label>
            {{ html()->textarea('custom_css_block')->class('form-control' . ($errors->has('custom_css_block') ? ' is-invalid' : ''))->value($data['custom_css_block'] ?? old('custom_css_block'))->placeholder(__('setting_custom_code.lbl_css_name'))->rows('5') }}
            @error('custom_css_block')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
        <div class="form-group">
            <label for="custom_js_block" class="form-label">{{ __('setting_custom_code.lbl_js_name') }} </label>
            {{ html()->textarea('custom_js_block')->class('form-control' . ($errors->has('custom_js_block') ? ' is-invalid' : ''))->value($data['custom_js_block'] ?? old('custom_js_block'))->placeholder(__('setting_custom_code.lbl_js_name'))->rows('5') }}
            @error('custom_js_block')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
        <div class="form-group text-end">
            <button type="submit" id="submit-button" class="btn btn-primary">{{ __('messages.save') }}</button>
        </div>
    </form>
@endsection
