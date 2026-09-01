@extends('backend.layouts.app')

@section('title')
    {{ __('mediaradar::mediaradar.add_rule') }}
@endsection

@section('content')
    <x-back-button-component route="backend.media-radar-rules.index" />

    {{ html()->form('POST', route('backend.media-radar-rules.store'))->class('requires-validation')->open() }}
    @csrf

    @include('mediaradar::backend.rules.form', ['rule' => null])

    <div class="mb-5">
        <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
        <a href="{{ route('backend.media-radar-rules.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
    </div>

    {{ html()->form()->close() }}
@endsection

@push('after-scripts')
    @include('mediaradar::backend.rules.form-script')
@endpush
