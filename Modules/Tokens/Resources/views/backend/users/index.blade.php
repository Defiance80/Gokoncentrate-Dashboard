@extends('backend.layouts.app')

@section('title') {{ __('tokens::tokens.users_title') }} @endsection

@section('content')
<div class="card">
    <div class="card-body">
        <!-- Section Header -->
        <x-backend.section-header>
            <div class="d-flex flex-wrap gap-3">
                <h4 class="mb-0"><i class="ph ph-users me-2"></i>{{ __('tokens::tokens.users_title') }}</h4>
            </div>

            <!-- Toolbar -->
            <x-slot name="toolbar">
                <div>
                    <!-- Earning Status Filter -->
                    <div class="datatable-filter">
                        <select name="earning_status" id="earning_status" class="select2 form-control"
                            data-filter="select" style="width: 100%">
                            <option value="">{{ __('messages.all') }}</option>
                            <option value="active" {{ ($filter['earning_status'] ?? '') == 'active' ? 'selected' : '' }}>
                                {{ __('tokens::tokens.status_active') }}
                            </option>
                            <option value="suspended" {{ ($filter['earning_status'] ?? '') == 'suspended' ? 'selected' : '' }}>
                                {{ __('tokens::tokens.status_suspended') }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Search Input -->
                <div class="input-group flex-nowrap">
                    <span class="input-group-text pe-0" id="addon-wrapping"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control dt-search" placeholder="{{ __('placeholder.lbl_search') }}" aria-label="Search"
                        aria-describedby="addon-wrapping">
                </div>
            </x-slot>
        </x-backend.section-header>

        <!-- Datatable -->
        <table id="datatable" class="table table-responsive">
        </table>
    </div>
</div>
@endsection

@push('after-styles')
<!-- DataTables Core and Extensions -->
<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
<script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
<script type="text/javascript" defer>
    const columns = [
        {
            name: 'check',
            data: 'check',
            title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
            width: '0%',
            exportable: false,
            orderable: false,
            searchable: false,
        },
        {
            data: 'name',
            name: 'name',
            title: "{{ __('tokens::tokens.lbl_user') }}"
        },
        {
            data: 'email',
            name: 'email',
            title: "{{ __('messages.email') }}"
        },
        {
            data: 'token_balance',
            name: 'token_balance',
            title: "{{ __('tokens::tokens.lbl_balance') }}",
            orderable: false,
            searchable: false,
        },
        {
            data: 'earning_status',
            name: 'earning_status',
            title: "{{ __('tokens::tokens.lbl_earning_status') }}",
            orderable: false,
            searchable: false,
        },
        {
            data: 'last_earned_at',
            name: 'last_earned_at',
            title: "{{ __('tokens::tokens.lbl_last_earned') }}",
            orderable: true,
        },
        {
            data: 'last_spent_at',
            name: 'last_spent_at',
            title: "{{ __('tokens::tokens.lbl_last_spent') }}",
            orderable: true,
        },
    ]

    const actionColumn = [{
        data: 'action',
        name: 'action',
        orderable: false,
        searchable: false,
        title: "{{ __('service.lbl_action') }}",
        width: '5%'
    }]

    let finalColumns = [
        ...columns,
        ...actionColumn
    ]

    document.addEventListener('DOMContentLoaded', (event) => {
        initDatatable({
            url: '{{ route("backend.token-users.index_data") }}',
            finalColumns,
            orderColumn: [[ 5, "desc" ]],
            advanceFilter: () => {
                return {
                    earning_status: $('#earning_status').val()
                }
            }
        });

        // Re-filter on status change
        $('#earning_status').on('change', function() {
            window.renderedDataTable.ajax.reload(null, false);
        });
    })
</script>
@endpush
