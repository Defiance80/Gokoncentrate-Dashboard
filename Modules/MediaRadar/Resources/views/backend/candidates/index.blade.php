@extends('backend.layouts.app')

@section('title')
    {{ __('mediaradar::mediaradar.candidates') }}
@endsection

@section('content')
    <div class="card-main mb-5">
        <x-backend.section-header>
            <div class="d-flex flex-wrap gap-3">
                <x-backend.quick-action url="{{ route('backend.media-radar-candidates.bulk_action') }}"
                    :entity_name="__('mediaradar::mediaradar.candidates')" :entity_name_plural="__('mediaradar::mediaradar.candidates')">
                    <div>
                        <select name="action_type" class="form-control select2 col-12" id="quick-action-type" style="width:100%">
                            <option value="">{{ __('messages.no_action') }}</option>
                            @hasPermission('approve_media_radar')
                                <option value="approve">{{ __('mediaradar::mediaradar.approve') }}</option>
                                <option value="reject">{{ __('mediaradar::mediaradar.reject') }}</option>
                            @endhasPermission
                            @hasPermission('edit_media_radar')
                                <option value="archive">{{ __('mediaradar::mediaradar.archive') }}</option>
                            @endhasPermission
                        </select>
                    </div>
                    {{-- The shared quick-action script reads the confirmation text from message_<action_type>. --}}
                    <input type="hidden" name="message_approve" value="{{ __('mediaradar::mediaradar.confirm_bulk_approve') }}">
                    <input type="hidden" name="message_reject" value="{{ __('mediaradar::mediaradar.confirm_bulk_reject') }}">
                    <input type="hidden" name="message_archive" value="{{ __('mediaradar::mediaradar.confirm_bulk_archive') }}">
                </x-backend.quick-action>
            </div>

            <x-slot name="toolbar">
                <div class="datatable-filter">
                    <select name="column_status" id="column_status" class="select2 form-control" data-filter="select" style="width:100%">
                        <option value="">{{ __('messages.all') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ ($filter['status'] ?? '') === $status ? 'selected' : '' }}>
                                {{ \Modules\MediaRadar\Support\CandidateStatus::label($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="datatable-filter">
                    <select name="provider" id="provider" class="select2 form-control" data-filter="select" style="width:100%">
                        <option value="">{{ __('mediaradar::mediaradar.lbl_providers') }}</option>
                        <option value="youtube">YouTube</option>
                        <option value="vimeo">Vimeo</option>
                    </select>
                </div>
                <div class="datatable-filter">
                    <select name="band" id="band" class="select2 form-control" data-filter="select" style="width:100%">
                        <option value="">{{ __('mediaradar::mediaradar.score') }}</option>
                        <option value="priority">90-100</option>
                        <option value="recommended">75-89</option>
                        <option value="secondary">60-74</option>
                        <option value="low">0-59</option>
                    </select>
                </div>
                <div class="datatable-filter">
                    <select name="genre_id" id="genre_id" class="select2 form-control" data-filter="select" style="width:100%">
                        <option value="">{{ __('mediaradar::mediaradar.lbl_genre') }}</option>
                        @foreach ($genres as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="datatable-filter">
                    <select name="rule_id" id="rule_id" class="select2 form-control" data-filter="select" style="width:100%">
                        <option value="">{{ __('mediaradar::mediaradar.rules') }}</option>
                        @foreach ($rules as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="input-group flex-nowrap">
                    <span class="input-group-text pe-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control dt-search" placeholder="{{ __('placeholder.lbl_search') }}" aria-label="Search">
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
            { data: 'check', name: 'check', title: '', orderable: false, searchable: false, width: '3%' },
            { data: 'poster_url', name: 'poster_url', title: "{{ __('mediaradar::mediaradar.cover_art') }}", orderable: false, searchable: false, width: '10%' },
            { data: 'original_title', name: 'original_title', title: "{{ __('video.lbl_title') }}" },
            { data: 'creator_name', name: 'creator_name', title: "{{ __('mediaradar::mediaradar.lbl_creator_name') }}" },
            { data: 'provider', name: 'provider', title: "{{ __('mediaradar::mediaradar.lbl_providers') }}", width: '8%' },
            { data: 'genre', name: 'genre_id', title: "{{ __('mediaradar::mediaradar.lbl_genre') }}", orderable: false, searchable: false },
            { data: 'duration', name: 'duration_seconds', title: "{{ __('mediaradar::mediaradar.lbl_duration') }}", searchable: false, width: '7%' },
            { data: 'editorial_score', name: 'editorial_score', title: "{{ __('mediaradar::mediaradar.score') }}", width: '6%' },
            { data: 'status', name: 'status', title: "{{ __('messages.lbl_status') }}" },
            { data: 'discovered_at', name: 'discovered_at', title: "{{ __('mediaradar::mediaradar.last_checked') }}" },
        ];

        const actionColumn = [{
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false,
            title: "{{ __('banner.lbl_action') }}",
            width: '12%'
        }];

        document.addEventListener('DOMContentLoaded', function () {
            initDatatable({
                url: '{{ route('backend.media-radar-candidates.index_data') }}',
                finalColumns: [...columns, ...actionColumn],
                orderColumn: [[7, 'desc']],
                // initDatatable only forwards column_status on its own.
                advanceFilter: () => ({
                    provider: jQuery('#provider').val(),
                    band: jQuery('#band').val(),
                    genre_id: jQuery('#genre_id').val(),
                    rule_id: jQuery('#rule_id').val()
                })
            });
        });
    </script>

    @include('mediaradar::backend.partials.actions-script')
@endpush
