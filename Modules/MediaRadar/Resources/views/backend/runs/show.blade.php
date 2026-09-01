@extends('backend.layouts.app')

@section('title')
    {{ __('mediaradar::mediaradar.runs') }} #{{ $run->id }}
@endsection

@section('content')
    <x-back-button-component route="backend.media-radar-runs.index" />

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                {{ optional($run->rule)->name ?: __('mediaradar::mediaradar.trusted_source_watch') }}
                <span class="text-muted">&middot; {{ ucfirst($run->provider) }}</span>
            </h5>
            <span class="badge bg-primary-subtle">{{ ucfirst($run->status) }}</span>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('messages.created_at') }}</dt>
                <dd class="col-sm-9">{{ $run->started_at ?: $run->created_at }}</dd>

                <dt class="col-sm-3">{{ __('mediaradar::mediaradar.lbl_duration') }}</dt>
                <dd class="col-sm-9">{{ $run->durationLabel() }}</dd>

                <dt class="col-sm-3">{{ __('mediaradar::mediaradar.results_received') }}</dt>
                <dd class="col-sm-9">{{ $run->results_received }}</dd>

                <dt class="col-sm-3">{{ __('mediaradar::mediaradar.new_candidates_col') }}</dt>
                <dd class="col-sm-9">{{ $run->new_candidates }}</dd>

                <dt class="col-sm-3">{{ __('mediaradar::mediaradar.existing_candidates') }}</dt>
                <dd class="col-sm-9">{{ $run->existing_candidates }}</dd>

                <dt class="col-sm-3">{{ __('mediaradar::mediaradar.filtered_out') }}</dt>
                <dd class="col-sm-9">{{ $run->filtered_out }}</dd>

                <dt class="col-sm-3">{{ __('mediaradar::mediaradar.provider_requests') }}</dt>
                <dd class="col-sm-9">{{ $run->provider_request_count }}</dd>

                <dt class="col-sm-3">{{ __('mediaradar::mediaradar.error_summary') }}</dt>
                <dd class="col-sm-9">
                    @if ($run->error_summary)
                        <div class="alert alert-danger mb-0">{{ $run->error_summary }}</div>
                    @else
                        -
                    @endif
                </dd>
            </dl>
        </div>
        @hasPermission('manage_media_radar_rules')
            @if ($run->rule_id)
                <div class="card-footer">
                    <button type="button" class="btn btn-warning media-radar-action"
                        data-url="{{ route('backend.media-radar-runs.retry', $run->id) }}"
                        data-redirect="{{ route('backend.media-radar-runs.index') }}">
                        <i class="ph ph-arrows-clockwise"></i> {{ __('mediaradar::mediaradar.run_now') }}
                    </button>
                </div>
            @endif
        @endhasPermission
    </div>
@endsection

@push('after-scripts')
    @include('mediaradar::backend.partials.actions-script')
@endpush
