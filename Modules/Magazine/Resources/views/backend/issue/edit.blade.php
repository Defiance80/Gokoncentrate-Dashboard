@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <x-back-button-component route="backend.magazine-issues.index" />
    {{ html()->modelForm($issue, 'PUT', route('backend.magazine-issues.update', $issue))->attribute('enctype', 'multipart/form-data')->class('requires-validation')->open() }}
    @csrf
    @php
        $print = $issue->printConfig;
    @endphp
    <div class="card">
        <div class="card-header">
            <h5>{{ __('magazine::edit_issue') }}</h5>
        </div>
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-6">
                    {{ html()->label(__('magazine::series'), 'magazine_id')->class('form-label') }}
                    {{ html()->select('magazine_id', $magazines->pluck('title', 'id'), $issue->magazine_id)->class('form-control select2')->required() }}
                </div>
                <div class="col-md-6">
                    {{ html()->label('Issue number', 'issue_number')->class('form-label') }}
                    {{ html()->text('issue_number', $issue->issue_number)->class('form-control') }}
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('video.lbl_title'), 'title')->class('form-label') }}
                    {{ html()->text('title', $issue->title)->class('form-control')->required() }}
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('messages.slug'), 'slug')->class('form-label') }}
                    {{ html()->text('slug', $issue->slug)->class('form-control')->required() }}
                </div>
                <div class="col-md-6">
                    {{ html()->label('Release date', 'release_date')->class('form-label') }}
                    {{ html()->date('release_date', $issue->release_date?->format('Y-m-d'))->class('form-control') }}
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('magazine::cover_image_url'), 'cover_image_url')->class('form-label') }}
                    {{ html()->text('cover_image_url', $issue->cover_image_url)->class('form-control') }}
                </div>
                <div class="col-12">
                    {{ html()->label('Summary', 'summary')->class('form-label') }}
                    {{ html()->textarea('summary', $issue->summary)->class('form-control')->rows(3) }}
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('messages.lbl_status'), 'status')->class('form-label') }}
                    {{ html()->select('status', ['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published'], $issue->status)->class('form-control select2') }}
                </div>
                <div class="col-md-6">
                    {{ html()->label('Visibility', 'visibility')->class('form-label') }}
                    {{ html()->select('visibility', ['public' => 'Public', 'subscribers' => 'Subscribers', 'paid-tier' => 'Paid tier'], $issue->visibility)->class('form-control select2') }}
                </div>
            </div>

            <hr class="my-4">
            <h6>{{ __('magazine::print_section') }}</h6>
            <div class="row gy-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        {{ html()->checkbox('print_enabled', $print && $print->print_enabled, 1)->class('form-check-input')->id('print_enabled') }}
                        {{ html()->label(__('magazine::print_enabled'), 'print_enabled')->class('form-check-label') }}
                    </div>
                </div>
                <div class="col-12 print-fields">
                    {{ html()->label(__('magazine::magcloud_product_url'), 'magcloud_product_url')->class('form-label') }}
                    {{ html()->text('magcloud_product_url', $print ? $print->magcloud_product_url : '')->class('form-control')->placeholder('https://www.magcloud.com/...') }}
                    <small class="text-muted">Required when print is enabled. Must be HTTPS and from magcloud.com.</small>
                </div>
                <div class="col-12 print-fields">
                    {{ html()->label(__('magazine::magcloud_viewer_url'), 'magcloud_viewer_url')->class('form-label') }}
                    {{ html()->text('magcloud_viewer_url', $print ? $print->magcloud_viewer_url : '')->class('form-control') }}
                </div>
                <div class="col-md-6 print-fields">
                    {{ html()->label(__('magazine::cta_label'), 'cta_label')->class('form-label') }}
                    @php
                        $ctaOptions = array_combine($ctaLabels, $ctaLabels);
                    @endphp
                    {{ html()->select('cta_label', $ctaOptions, $print ? $print->cta_label : 'Order Print Copy')->class('form-control select2') }}
                </div>
                <div class="col-12 print-fields">
                    {{ html()->label(__('magazine::notes_internal'), 'notes_internal')->class('form-label') }}
                    {{ html()->textarea('notes_internal', $print ? $print->notes_internal : '')->class('form-control')->rows(2) }}
                </div>
                @if ($print && $print->print_enabled && $print->magcloud_product_url)
                    <div class="col-12 print-fields">
                        <a href="{{ url('/r/magazine/' . $issue->id . '/print') }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                            <i class="ph ph-open-in-browser"></i> {{ __('magazine::test_open') }}
                        </a>
                        <small class="text-muted ms-2">Opens redirect URL in new tab (tracking + MagCloud).</small>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
            <a href="{{ route('backend.magazine-issues.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
        </div>
    </div>
    {{ html()->form()->close() }}
@endsection

@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toggle = document.getElementById('print_enabled');
            var fields = document.querySelectorAll('.print-fields');
            function update() {
                fields.forEach(function(el) { el.style.display = toggle.checked ? '' : 'none'; });
            }
            toggle.addEventListener('change', update);
            update();
        });
    </script>
@endpush
