@extends('backend.layouts.app')

@section('title')
    {{ __('mediaradar::mediaradar.sources') }}
@endsection

@section('content')
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <button class="nav-link {{ $tab !== 'blocked' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#trusted-pane" type="button">
                {{ __('mediaradar::mediaradar.trusted_tab') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $tab === 'blocked' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#blocked-pane" type="button">
                {{ __('mediaradar::mediaradar.blocked_tab') }}
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $tab !== 'blocked' ? 'show active' : '' }}" id="trusted-pane">
            @hasPermission('manage_media_radar_sources')
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">{{ __('mediaradar::mediaradar.add_trusted') }}</h5></div>
                    <div class="card-body">
                        <form id="trusted-form" class="row gy-2 gx-2 align-items-end"
                            data-url="{{ route('backend.media-radar-sources.store_trusted') }}">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('mediaradar::mediaradar.lbl_providers') }}</label>
                                <select name="provider" class="form-control">
                                    @foreach ($providerOptions as $slug => $label)
                                        <option value="{{ $slug }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('mediaradar::mediaradar.lbl_creator_id') }}</label>
                                <input type="text" name="provider_creator_id" class="form-control" placeholder="UC..." required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('mediaradar::mediaradar.lbl_creator_name') }}</label>
                                <input type="text" name="creator_name" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('mediaradar::mediaradar.lbl_genre') }}</label>
                                <select name="default_genre_id" class="form-control">
                                    <option value="">-</option>
                                    @foreach ($genres as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">{{ __('messages.save') }}</button>
                            </div>
                            <div class="col-12">
                                <small class="text-muted">{{ __('mediaradar::mediaradar.lbl_creator_id_help') }}</small>
                            </div>
                        </form>
                    </div>
                </div>
            @endhasPermission

            <div class="card-main mb-5">
                <table id="trusted-datatable" class="table table-responsive"></table>
            </div>
        </div>

        <div class="tab-pane fade {{ $tab === 'blocked' ? 'show active' : '' }}" id="blocked-pane">
            @hasPermission('manage_media_radar_sources')
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">{{ __('mediaradar::mediaradar.add_blocked') }}</h5></div>
                    <div class="card-body">
                        <form id="blocked-form" class="row gy-2 gx-2 align-items-end"
                            data-url="{{ route('backend.media-radar-sources.store_blocked') }}">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('mediaradar::mediaradar.lbl_providers') }}</label>
                                <select name="provider" class="form-control">
                                    @foreach ($providerOptions as $slug => $label)
                                        <option value="{{ $slug }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('mediaradar::mediaradar.lbl_creator_id') }}</label>
                                <input type="text" name="provider_creator_id" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('mediaradar::mediaradar.lbl_creator_name') }}</label>
                                <input type="text" name="creator_name" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('mediaradar::mediaradar.rejection_reason') }}</label>
                                <input type="text" name="reason" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-danger w-100">{{ __('mediaradar::mediaradar.block_creator') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endhasPermission

            <div class="card-main mb-5">
                <table id="blocked-datatable" class="table table-responsive"></table>
            </div>
        </div>
    </div>
@endsection

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            /*
             * Two independent tables on one page, so they are initialised
             * directly rather than through the shared single-table helper.
             */
            const trustedTable = window.jQuery('#trusted-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('backend.media-radar-sources.trusted_data') }}',
                order: [[0, 'desc']],
                columns: [
                    { data: 'id', name: 'id', title: 'ID', width: '5%' },
                    { data: 'provider', name: 'provider', title: "{{ __('mediaradar::mediaradar.lbl_providers') }}" },
                    { data: 'creator_name', name: 'creator_name', title: "{{ __('mediaradar::mediaradar.lbl_creator_name') }}" },
                    { data: 'provider_creator_id', name: 'provider_creator_id', title: "{{ __('mediaradar::mediaradar.lbl_creator_id') }}" },
                    { data: 'genre', name: 'default_genre_id', title: "{{ __('mediaradar::mediaradar.lbl_genre') }}", orderable: false, searchable: false },
                    { data: 'approval_rate', name: 'approval_rate', title: "{{ __('mediaradar::mediaradar.approval_rate') }}", orderable: false, searchable: false },
                    { data: 'totals', name: 'totals', title: "{{ __('mediaradar::mediaradar.approved_rejected') }}", orderable: false, searchable: false },
                    { data: 'publishing_mode', name: 'publishing_mode', title: "{{ __('mediaradar::mediaradar.lbl_publishing_mode') }}" },
                    { data: 'last_checked', name: 'last_checked_at', title: "{{ __('mediaradar::mediaradar.last_checked') }}", searchable: false },
                    { data: 'enabled', name: 'enabled', title: "{{ __('messages.lbl_status') }}" },
                    { data: 'action', name: 'action', title: "{{ __('banner.lbl_action') }}", orderable: false, searchable: false }
                ]
            });

            const blockedTable = window.jQuery('#blocked-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('backend.media-radar-sources.blocked_data') }}',
                order: [[0, 'desc']],
                columns: [
                    { data: 'id', name: 'id', title: 'ID', width: '5%' },
                    { data: 'provider', name: 'provider', title: "{{ __('mediaradar::mediaradar.lbl_providers') }}" },
                    { data: 'creator_name', name: 'creator_name', title: "{{ __('mediaradar::mediaradar.lbl_creator_name') }}" },
                    { data: 'provider_creator_id', name: 'provider_creator_id', title: "{{ __('mediaradar::mediaradar.lbl_creator_id') }}" },
                    { data: 'reason', name: 'reason', title: "{{ __('mediaradar::mediaradar.rejection_reason') }}" },
                    { data: 'created_at', name: 'created_at', title: "{{ __('messages.created_at') }}" },
                    { data: 'action', name: 'action', title: "{{ __('banner.lbl_action') }}", orderable: false, searchable: false }
                ]
            });

            window.mediaRadarTables = { trusted: trustedTable, blocked: blockedTable };

            function submitForm(form, table) {
                if (!form) {
                    return;
                }

                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    const payload = {};
                    new FormData(form).forEach(function (value, key) {
                        payload[key] = value;
                    });

                    fetch(form.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ title: '', text: data.message || '', icon: data.status ? 'success' : 'error' });
                            }

                            if (data.status) {
                                form.reset();
                                table.ajax.reload(null, false);
                            }
                        });
                });
            }

            submitForm(document.getElementById('trusted-form'), trustedTable);
            submitForm(document.getElementById('blocked-form'), blockedTable);
        });
    </script>

    @include('mediaradar::backend.partials.actions-script')
@endpush
