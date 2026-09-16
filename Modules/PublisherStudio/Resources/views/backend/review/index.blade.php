@extends('backend.layouts.app')

@section('title'){{ __('publisherstudio::studio.review_queue') }}@endsection

@section('content')
    <div class="card-main mb-5">
        <x-backend.section-header>
            <x-slot name="toolbar">
                <a href="{{ route('backend.publisher-storage.edit') }}" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                    <i class="ph ph-hard-drives"></i> {{ __('publisherstudio::studio.storage_connections') }}
                </a>
            </x-slot>
        </x-backend.section-header>
        <table id="datatable" class="table table-responsive"></table>
    </div>
    @if (session('status'))
        <div class="snackbar" id="snackbar"><div class="d-flex justify-content-around align-items-center"><p class="mb-0">{{ session('status') }}</p><a href="#" class="dismiss-link text-decoration-none text-success" onclick="dismissSnackbar(event)">{{ __('messages.dismiss') }}</a></div></div>
    @endif
@endsection

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush
@push('after-scripts')
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
    <script type="text/javascript" defer>
        const columns = [
            { data: 'id', name: 'id', title: 'ID', width: '5%' },
            { data: 'title', name: 'title', title: 'Title' },
            { data: 'publisher', name: 'publisher', title: 'Publisher', orderable: false, searchable: false },
            { data: 'type_label', name: 'type', title: 'Type' },
            { data: 'status', name: 'status', title: 'Status' },
        ];
        const actionColumn = [{ data: 'action', name: 'action', orderable: false, searchable: false, title: 'Action', width: '10%' }];
        document.addEventListener('DOMContentLoaded', function() {
            initDatatable({
                url: '{{ route("backend.publisher-submissions.index_data") }}',
                finalColumns: [...columns, ...actionColumn],
                orderColumn: [[0, 'desc']]
            });
        });
    </script>
@endpush
