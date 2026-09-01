@extends('backend.layouts.app')

@section('title')
    {{ __('mediaradar::mediaradar.runs') }}
@endsection

@section('content')
    <div class="card-main mb-5">
        <x-backend.section-header>
            <x-slot name="toolbar">
                @if (! empty($ruleName))
                    <span class="badge bg-secondary-subtle align-self-center">{{ $ruleName }}</span>
                @endif
                <div class="datatable-filter">
                    <select name="provider" id="provider" class="select2 form-control" data-filter="select" style="width:100%">
                        <option value="">{{ __('mediaradar::mediaradar.lbl_providers') }}</option>
                        <option value="youtube">YouTube</option>
                        <option value="vimeo">Vimeo</option>
                    </select>
                </div>
                <div class="datatable-filter">
                    <select name="column_status" id="column_status" class="select2 form-control" data-filter="select" style="width:100%">
                        <option value="">{{ __('messages.all') }}</option>
                        <option value="queued">Queued</option>
                        <option value="running">Running</option>
                        <option value="completed">Completed</option>
                        <option value="partial">Partial</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </x-slot>
        </x-backend.section-header>

        <table id="datatable" class="table table-responsive"></table>
    </div>
@endsection

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
    <script type="text/javascript" defer>
        const columns = [
            { data: 'id', name: 'id', title: 'ID', width: '5%' },
            { data: 'rule', name: 'rule_id', title: "{{ __('mediaradar::mediaradar.rule') }}", orderable: false, searchable: false },
            { data: 'provider', name: 'provider', title: "{{ __('mediaradar::mediaradar.lbl_providers') }}" },
            { data: 'status', name: 'status', title: "{{ __('messages.lbl_status') }}" },
            { data: 'started_at', name: 'started_at', title: "{{ __('messages.created_at') }}" },
            { data: 'duration', name: 'duration', title: "{{ __('mediaradar::mediaradar.lbl_duration') }}", orderable: false, searchable: false },
            { data: 'results_received', name: 'results_received', title: "{{ __('mediaradar::mediaradar.results_received') }}" },
            { data: 'new_candidates', name: 'new_candidates', title: "{{ __('mediaradar::mediaradar.new_candidates_col') }}" },
            { data: 'existing_candidates', name: 'existing_candidates', title: "{{ __('mediaradar::mediaradar.existing_candidates') }}" },
            { data: 'filtered_out', name: 'filtered_out', title: "{{ __('mediaradar::mediaradar.filtered_out') }}" },
            { data: 'provider_request_count', name: 'provider_request_count', title: "{{ __('mediaradar::mediaradar.provider_requests') }}" },
        ];

        const actionColumn = [{
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false,
            title: "{{ __('banner.lbl_action') }}",
            width: '10%'
        }];

        document.addEventListener('DOMContentLoaded', function () {
            initDatatable({
                url: '{{ route('backend.media-radar-runs.index_data') }}',
                finalColumns: [...columns, ...actionColumn],
                orderColumn: [[0, 'desc']],
                advanceFilter: () => ({
                    provider: jQuery('#provider').val(),
                    rule_id: @json($ruleId)
                })
            });
        });
    </script>

    @include('mediaradar::backend.partials.actions-script')
@endpush
