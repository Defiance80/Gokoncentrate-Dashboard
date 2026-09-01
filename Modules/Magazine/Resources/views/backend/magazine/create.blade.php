@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <x-back-button-component route="backend.magazines.index" />
    {{ html()->form('POST', route('backend.magazines.store'))->attribute('enctype', 'multipart/form-data')->class('requires-validation')->open() }}
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-6">
                    {{ html()->label(__('video.lbl_title'), 'title')->class('form-label') }}
                    {{ html()->text('title')->class('form-control')->placeholder(__('video.lbl_title'))->required() }}
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('messages.slug'), 'slug')->class('form-label') }}
                    {{ html()->text('slug')->class('form-control')->placeholder(__('messages.slug'))->required() }}
                </div>
                <div class="col-12">
                    {{ html()->label(__('messages.description'), 'description')->class('form-label') }}
                    {{ html()->textarea('description')->class('form-control')->rows(3) }}
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('magazine::cover_image_url'), 'cover_image_url')->class('form-label') }}
                    {{ html()->text('cover_image_url')->class('form-control')->placeholder('https://...') }}
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('magazine::theme_color'), 'theme_color')->class('form-label') }}
                    {{ html()->text('theme_color')->class('form-control')->placeholder('#hex') }}
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('messages.lbl_status'), 'status')->class('form-label') }}
                    {{ html()->select('status', ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'], 'draft')->class('form-control select2') }}
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
            <a href="{{ route('backend.magazines.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
