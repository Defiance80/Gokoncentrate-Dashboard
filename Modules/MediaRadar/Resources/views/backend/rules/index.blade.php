@extends('backend.layouts.app')

@section('title')
    {{ __('mediaradar::mediaradar.rules') }}
@endsection

@section('content')
    <div class="card-main mb-5">
        <x-backend.section-header>
            <x-slot name="toolbar">
                <div class="input-group flex-nowrap">
                    <span class="input-group-text pe-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control dt-search" placeholder="{{ __('placeholder.lbl_search') }}" aria-label="Search">
                </div>
                @hasPermission('manage_media_radar_rules')
                    <a href="{{ route('backend.media-radar-rules.create') }}" class="btn btn-primary d-flex align-items-center gap-1">
                        <i class="ph ph-plus-circle"></i> {{ __('mediaradar::mediaradar.add_rule') }}
                    </a>
                @endhasPermission
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
            { data: 'name', name: 'name', title: "{{ __('mediaradar::mediaradar.lbl_name') }}" },
            { data: 'providers', name: 'providers', title: "{{ __('mediaradar::mediaradar.lbl_providers') }}", orderable: false },
            { data: 'genre', name: 'genre_id', title: "{{ __('mediaradar::mediaradar.lbl_genre') }}", orderable: false, searchable: false },
            { data: 'schedule', name: 'schedule_type', title: "{{ __('mediaradar::mediaradar.lbl_schedule_type') }}" },
            { data: 'last_run', name: 'last_run_at', title: "{{ __('mediaradar::mediaradar.last_checked') }}", searchable: false },
            { data: 'next_run', name: 'next_run_at', title: "{{ __('mediaradar::mediaradar.next_run') }}", searchable: false },
            { data: 'candidates', name: 'rule_matches_count', title: "{{ __('mediaradar::mediaradar.candidates') }}", orderable: false, searchable: false },
            { data: 'approval_rate', name: 'approval_rate', title: "{{ __('mediaradar::mediaradar.approval_rate') }}", orderable: false, searchable: false },
            { data: 'enabled', name: 'enabled', title: "{{ __('messages.lbl_status') }}" },
        ];

        const actionColumn = [{
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false,
            title: "{{ __('banner.lbl_action') }}",
            width: '14%'
        }];

        document.addEventListener('DOMContentLoaded', function () {
            initDatatable({
                url: '{{ route('backend.media-radar-rules.index_data') }}',
                finalColumns: [...columns, ...actionColumn],
                orderColumn: [[0, 'desc']]
            });
        });
    </script>

    @include('mediaradar::backend.partials.actions-script')
@endpush
