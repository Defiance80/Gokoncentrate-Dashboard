@extends('backend.layouts.app')
@section('title')Publishers @endsection
@section('content')
    <div class="card-main mb-5">
        <x-backend.section-header></x-backend.section-header>
        <table id="datatable" class="table table-responsive"></table>
    </div>
    @if (session('status'))
        <div class="snackbar" id="snackbar"><div class="d-flex justify-content-around align-items-center"><p class="mb-0">{{ session('status') }}</p><a href="#" class="dismiss-link text-decoration-none text-success" onclick="dismissSnackbar(event)">{{ __('messages.dismiss') }}</a></div></div>
    @endif
@endsection
@push('after-styles')<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">@endpush
@push('after-scripts')
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
    <script type="text/javascript" defer>
        const columns = [
            { data: 'id', name: 'id', title: 'ID', width: '5%' },
            { data: 'name', name: 'name', title: 'Name' },
            { data: 'company', name: 'company', title: 'Publication' },
            { data: 'content_focus', name: 'content_focus', title: 'Focus' },
            { data: 'submissions', name: 'submissions', title: 'Subs', orderable: false, searchable: false },
            { data: 'status', name: 'status', title: 'Status' },
        ];
        const actionColumn = [{ data: 'action', name: 'action', orderable: false, searchable: false, title: 'Action', width: '10%' }];
        document.addEventListener('DOMContentLoaded', function() {
            initDatatable({ url: '{{ route("backend.publishers.index_data") }}', finalColumns: [...columns, ...actionColumn], orderColumn: [[0, 'desc']] });
        });
    </script>
@endpush
