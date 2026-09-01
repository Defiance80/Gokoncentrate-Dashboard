@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <div class="card-main mb-5">
        <x-backend.section-header>
            <x-slot name="toolbar">
                <a href="{{ route('backend.magazines.create') }}" class="btn btn-primary d-flex align-items-center gap-1">
                    <i class="ph ph-plus-circle"></i> {{ __('messages.new') }}
                </a>
            </x-slot>
        </x-backend.section-header>
        <table id="datatable" class="table table-responsive"></table>
    </div>
    @if (session('success'))
        <div class="snackbar" id="snackbar">
            <div class="d-flex justify-content-around align-items-center">
                <p class="mb-0">{{ session('success') }}</p>
                <a href="#" class="dismiss-link text-decoration-none text-success" onclick="dismissSnackbar(event)">{{ __('messages.dismiss') }}</a>
            </div>
        </div>
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
            { data: 'title', name: 'title', title: "{{ __('video.lbl_title') }}" },
            { data: 'slug', name: 'slug', title: "{{ __('messages.slug') }}" },
            { data: 'status', name: 'status', title: "{{ __('messages.lbl_status') }}" },
        ];
        const actionColumn = [{
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false,
            title: "{{ __('banner.lbl_action') }}",
            width: '10%'
        }];
        document.addEventListener('DOMContentLoaded', function() {
            initDatatable({
                url: '{{ route("backend.magazines.index_data") }}',
                finalColumns: [...columns, ...actionColumn],
                orderColumn: [[0, 'desc']]
            });
        });
    </script>
@endpush
