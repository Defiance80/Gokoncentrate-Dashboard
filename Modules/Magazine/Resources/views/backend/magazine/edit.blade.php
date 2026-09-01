@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <x-back-button-component route="backend.magazines.index" />
    {{ html()->modelForm($magazine, 'PUT', route('backend.magazines.update', $magazine))->attribute('enctype', 'multipart/form-data')->class('requires-validation')->open() }}
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-6">
                    {{ html()->label(__('video.lbl_title'), 'title')->class('form-label') }}
                    {{ html()->text('title', $magazine->title)->class('form-control')->required() }}
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('messages.slug'), 'slug')->class('form-label') }}
                    {{ html()->text('slug', $magazine->slug)->class('form-control')->required() }}
                </div>
                <div class="col-12">
                    {{ html()->label(__('messages.description'), 'description')->class('form-label') }}
                    {{ html()->textarea('description', $magazine->description)->class('form-control')->rows(3) }}
                </div>
                <div class="col-md-6">
                    {{ html()->label('Cover image URL', 'cover_image_url')->class('form-label') }}
                    {{ html()->text('cover_image_url', $magazine->cover_image_url)->class('form-control') }}
                </div>
                <div class="col-md-6">
                    {{ html()->label('Theme color', 'theme_color')->class('form-label') }}
                    {{ html()->text('theme_color', $magazine->theme_color)->class('form-control') }}
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('messages.lbl_status'), 'status')->class('form-label') }}
                    {{ html()->select('status', ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'], $magazine->status)->class('form-control select2') }}
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
            <a href="{{ route('backend.magazines.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
