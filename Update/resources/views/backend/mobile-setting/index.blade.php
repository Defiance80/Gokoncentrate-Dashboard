@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <div class="mb-3">
        <div class="header-title d-flex align-items-center justify-content-between">
            <h4 class="mb-0">{{ __('settings.change_layout_order') }}</h4>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModalSection">
                {{ __('messages.add_mobile_setting') }}
            </button>
        </div>

        @if (session('success'))
            <div class="snackbar mt-3" id="snackbar">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="mb-0">{{ session('success') }}</p>
                    <a href="#" class="dismiss-link text-decoration-none text-success"
                        onclick="dismissSnackbar(event)">{{ __('messages.dismiss') }}</a>
                </div>
            </div>
        @endif
    </div>

    <div id="sortable" class="mb-5">

        @foreach ($data as $mobile_setting)
            <div class="d-flex align-items-center gap-4 mobile-setting-row mt-5" data-id="{{ $mobile_setting->id }}"
                data-position="{{ $mobile_setting->position }}">

                <div class="flex-grow-1">

                    <div class="card mb-0">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <h5 class="m-0">{{ $mobile_setting->name }}</h5>
                                @if (
                                    $mobile_setting->slug == 'banner' ||
                                        $mobile_setting->slug == 'continue-watching' ||
                                        $mobile_setting->slug == 'advertisement' ||
                                        $mobile_setting->slug == 'rate-our-app')
                                    <div class="form-check form-switch">
                                        {{ html()->hidden('value', 0) }}
                                        {{ html()->checkbox('value', old('value', $mobile_setting->value))->class('form-check-input status-switch')->id('value')->value(1)->data('id', $mobile_setting->id)->data('name', $mobile_setting->name)->data('position', $mobile_setting->position) }}
                                    </div>
                                @endif
                                @if (
                                    $mobile_setting->slug !== 'banner' &&
                                        $mobile_setting->slug !== 'continue-watching' &&
                                        $mobile_setting->slug !== 'advertisement' &&
                                        $mobile_setting->slug !== 'rate-our-app')
                                    <div class="d-flex align-items-center gap-2 justify-content-end">
                                        @hasPermission('edit_dashboard_setting')
                                            <button class="btn btn-warning-subtle btn-sm fs-4 edit-button"
                                                data-id="{{ $mobile_setting->id }}">
                                                <i class="ph ph-pencil-simple-line align-middle"></i>
                                            </button>
                                        @endhasPermission

                                        <button class="collapsed btn btn-success-subtle btn-sm fs-4 accordion-btn"
                                            data-id="{{ $mobile_setting->id }}" data-type="{{ $mobile_setting->type }}"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#accordian_btn_{{ $mobile_setting->id }}" aria-expanded="false"
                                            aria-controls="accordian_btn_{{ $mobile_setting->id }}">
                                            <i class="ph ph-plus align-middle"></i>
                                        </button>

                                        @hasPermission('delete_dashboard_setting')
                                            <button class="btn btn-danger-subtle btn-sm fs-4 delete-button"
                                                data-id="{{ $mobile_setting->id }}"
                                                data-name="{{ $mobile_setting->name }}">
                                                <i class="ph ph-trash align-middle"></i>
                                            </button>
                                        @endhasPermission
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div id="accordian_btn_{{ $mobile_setting->id }}" class="accordion-collapse collapse"
                        aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                        <div class="accordion-body">
                            {{ html()->form('POST', route('backend.mobile-setting.store'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->open() }}
                            @csrf
                            {{ html()->hidden('id')->value($mobile_setting->id) }}
                            {{ html()->hidden('name')->value($mobile_setting->name) }}
                            {{ html()->hidden('position')->value($mobile_setting->position) }}

                            <div class="mb-3">
                                {{ html()->label(__('movie.lbl_select') . ' ' . $mobile_setting->name . '<span class="text-danger">*</span>', 'dashboard_select')->class('form-label') }}
                                {{ html()->select('dashboard_select[]', old('dashboard_select'))->class('form-control select2')->id('dashboard_select_' . $mobile_setting->id)->multiple()->attribute('data-placeholder', __('placeholder.lbl_select_value')) }}
                                @error('dashboard_select')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="text-end">
                                {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right') }}
                            </div>

                            {{ html()->form()->close() }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>


    <div class="modal fade @if ($errors->any()) show @endif" id="addModal" data-bs-backdrop="static"
        tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{ __('settings.mobile_setting') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form action="{{ route('backend.mobile-setting.addNewRequest') }}" method="POST"
                        data-toggle="validator">
                        @csrf
                        {{ html()->hidden('id')->id('mobileSettingId')->value(isset($mobileSetting) ? $mobileSetting->id : '') }}

                        <div class="mb-3">
                            {{ html()->label(__('settings.lbl_name') . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                            {{ html()->text('name')->attribute('value', old('name'))->placeholder(__('placeholder.lbl_mobile_setting_name'))->class('form-control') }}
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            {{ html()->label(__('movie.type') . '<span class="text-danger">*</span>', 'type')->class('form-label') }}
                            @php
                                $selectedTypeSlug = old('type', isset($mobileSetting) ? $mobileSetting->slug : '');
                                $selectedType = $typeValue->firstWhere('slug', $selectedTypeSlug);
                                $selectedTypeName = $selectedType ? $selectedType->name : '';
                            @endphp
                            {{ html()->text('type_display')->attribute('value', $selectedTypeName)->class('form-control')->attribute('disabled', true)->id('type_display') }}
                            {{ html()->hidden('type')->attribute('value', $selectedTypeSlug)->id('type') }}
                            @error('type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12" id="type_value">
                            {{ html()->label(__('movie.lbl_value'), 'optionvalue')->class('form-label') }}
                            {{ html()->select('optionvalue[]')->class('form-control select2')->id('optionvalue')->multiple()->attribute('data-placeholder', __('placeholder.lbl_select_value')) }}
                            @error('optionvalue')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="modal-footer">
                            <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mt-5">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
                                {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right') }}
                            </div>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade @if ($errors->any()) show @endif" id="addModalSection" data-bs-backdrop="static"
        tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{ __('settings.mobile_setting') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form id="mobileSettingSectionForm"
                        action="{{ route('backend.mobile-setting.addNewRequestSection') }}" method="POST"
                        data-toggle="validator">
                        @csrf
                        {{ html()->hidden('id')->id('mobileSettingId')->value(isset($mobileSetting) ? $mobileSetting->id : '') }}

                        <div class="mb-3">
                            {{ html()->label(__('settings.lbl_name') . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                            {{ html()->text('name')->attribute('value', old('name'))->placeholder(__('placeholder.lbl_mobile_setting_name'))->class('form-control') }}
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            {{ html()->label(__('movie.type') . '<span class="text-danger">*</span>', 'section_type')->class('form-label') }}
                            {{ html()->select('section_type', [
                                    '' => __('placeholder.lbl_select_type'),
                                    'movie' => __('movie.movie'),
                                    'tvshow' => __('messages.tvshow'),
                                    'video' => __('messages.video'),
                                    'channel' => __('messages.channel'),
                                ])->class('form-control select2')->id('section_type') }}
                            @error('section_type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12" id="type_value">
                            {{ html()->label(__('movie.lbl_value'), 'optionvalueSection')->class('form-label') }}
                            {{ html()->select('optionvalue[]')->class('form-control select2')->id('optionvalueSection')->multiple()->attribute('data-placeholder', __('placeholder.lbl_select_value')) }}
                            @error('optionvalueSection')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="modal-footer">
                            <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mt-5">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
                                {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right') }}
                            </div>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('after-styles')
    <!-- DataTables Core and Extensions -->
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
    <style>
        .select2-container {
            z-index: 2050;
            /* Adjust this value if needed to ensure it stays above the modal's background */
        }
        /* Make Select2 placeholder text bold for better visibility */
        .select2-selection__placeholder {
            font-weight: bold !important;
            color: #6c757d !important;
        }
        .select2-search--inline .select2-search__field::placeholder {
            font-weight: bold !important;
            color: #6c757d !important;
        }
    </style>
@endpush

@push('after-scripts')
    <!-- DataTables Core and Extensions -->
    <script src="{{ asset('js/form-modal/index.js') }}" defer></script>
    <script src="{{ asset('js/form/index.js') }}" defer></script>
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.6/dist/sweetalert2.all.min.js"></script>

    <script>
        // Type value map for display
        const typeValueMap = @json($typeValue->pluck('name', 'slug'));

        // Check if global Select2 is interfering


        // Override global Select2 initialization for mobile setting page
        $(document).ready(function() {


            // Wait for global Select2 to initialize, then fix all elements
            setTimeout(function() {
                // Fix all Select2 elements that were initialized globally
                $('.select2').each(function() {
                    var $this = $(this);
                    var elementId = $this.attr('id');

                    if ($this.hasClass('select2-hidden-accessible')) {


                        // Destroy existing Select2
                        try {
                            $this.select2('destroy');

                        } catch(e) {

                        }

                        // Reinitialize with search enabled
                        var options = {
                            minimumResultsForSearch: 0,
                            width: '100%',
                            allowClear: true
                        };

                        // Add specific options based on element
                        if (elementId === 'type') {
                            // Skip Select2 for type field - it's now a disabled input
                            return;
                        } else if (elementId === 'section_type') {
                            options.placeholder = '{{ __('placeholder.lbl_select_type') }}';
                            options.dropdownParent = $('#addModalSection');
                        } else if (elementId === 'optionvalue' || elementId === 'optionvalueSection') {
                            var placeholder = $this.data('placeholder') || '{{ __('placeholder.lbl_select_value') }}';
                            options.placeholder = placeholder;
                            if (elementId === 'optionvalue') {
                                options.dropdownParent = $('#addModal');
                            } else {
                                options.dropdownParent = $('#addModalSection');
                            }
                        } else if (elementId && elementId.startsWith('dashboard_select_')) {
                            var placeholder = $this.data('placeholder') || '{{ __('placeholder.lbl_select_value') }}';
                            options.placeholder = placeholder;
                        }

                        // Reinitialize with search enabled
                        try {
                            $this.select2(options);

                        } catch(e) {

                        }
                    }
                });
            }, 100); // Wait 100ms for global initialization
        });

        document.addEventListener("DOMContentLoaded", function() {
            const addModalElement = document.getElementById('addModal');
            const addModalInstance = new bootstrap.Modal(addModalElement, {});

            @if ($errors->any())
                addModalInstance.show();
                addModalElement.addEventListener('hide.bs.modal', function(event) {
                    event.preventDefault();
                });
            @endif

            // Initialize Select2 when modal is shown
            addModalElement.addEventListener('shown.bs.modal', function() {

                // Type field is now a disabled input, no Select2 needed

                if ($('#optionvalue').length && !$('#optionvalue').hasClass('select2-hidden-accessible')) {

                    $('#optionvalue').select2({
                        placeholder: $('#optionvalue').data('placeholder') || '{{ __('placeholder.lbl_select_value') }}',
                        allowClear: true,
                        dropdownParent: $('#addModal'),
                        minimumResultsForSearch: 0,
                        width: '100%'
                    });


                    // Check if search box exists after initialization
                    setTimeout(function() {
                        var searchBox = $('#optionvalue').next('.select2-container').find('.select2-search__field');

                        if (searchBox.length > 0) {

                        }
                    }, 100);

                    // Listen for when all tags are removed
                    $('#optionvalue').on('select2:unselect', function() {
                        var selectedValues = $(this).val();
                        if (!selectedValues || selectedValues.length === 0) {
                            // Force placeholder to show by clearing the value
                            $(this).val(null).trigger('change');
                        }
                    });
                } else {

                }
            });

            addModalElement.addEventListener('hidden.bs.modal', function() {
                addModalElement.querySelectorAll('input:not([name="_token"])').forEach(input => input
                    .value = '');
                addModalElement.querySelectorAll('textarea').forEach(textarea => textarea.value = '');
            });
        });

        // Initialize Select2 for addModalSection when shown
        document.addEventListener("DOMContentLoaded", function() {
            const addModalSectionElement = document.getElementById('addModalSection');
            if (addModalSectionElement) {
                const sectionForm = document.getElementById('mobileSettingSectionForm');

                addModalSectionElement.addEventListener('show.bs.modal', function() {
                    $(addModalSectionElement).find('input[name="name"]').val('');
                });

                addModalSectionElement.addEventListener('shown.bs.modal', function() {
                    if ($('#optionvalueSection').length && !$('#optionvalueSection').hasClass('select2-hidden-accessible')) {
                        $('#optionvalueSection').select2({
                            placeholder: $('#optionvalueSection').data('placeholder') || '{{ __('placeholder.lbl_select_value') }}',
                            allowClear: true,
                            dropdownParent: $('#addModalSection'),
                            minimumResultsForSearch: 0
                        });

                        // Listen for when all tags are removed
                        $('#optionvalueSection').on('select2:unselect', function() {
                            var selectedValues = $(this).val();
                            if (!selectedValues || selectedValues.length === 0) {
                                // Force placeholder to show by clearing the value
                                $(this).val(null).trigger('change');
                            }
                        });
                    }
                });

                addModalSectionElement.addEventListener('hidden.bs.modal', function () {
                    if (sectionForm) {
                        sectionForm.reset();
                    }

                    // Reset Select2 fields
                    $('#section_type').val('').trigger('change');
                    $('#optionvalueSection').val(null).trigger('change');

                    $(addModalSectionElement).find('.text-danger').text('');
                });
            }
        });

        function showMessage(message) {
            Snackbar.show({
                text: message,
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            $('.edit-button').on('click', function() {
                var id = $(this).data('id');
                var editUrl = '{{ route('backend.mobile-setting.edit', ':id') }}';
                editUrl = editUrl.replace(':id', id);

                $.ajax({
                    url: editUrl,
                    method: 'GET',
                    success: function(data) {
                        $('#mobileSettingId').val(data.id);
                        $('input[name="name"]').val(data.name);
                        $('input[name="position"]').val(data.position);

                        $('#type').val(data.slug);
                        $('#type_display').val(typeValueMap[data.slug] || '');

                        // Show modal first, then initialize Select2 after it's shown
                        $('#addModal').modal('show');

                        // Use one-time event listener to initialize after modal is shown
                        $('#addModal').one('shown.bs.modal', function() {
                            // Type field is now disabled input, just trigger change for optionvalue
                            $('#type').trigger('change');

                            // Clear existing options in 'optionvalue'
                            if ($('#optionvalue').hasClass('select2-hidden-accessible')) {
                                $('#optionvalue').select2('destroy');
                            }
                            $('#optionvalue').html('');

                            // Reinitialize Select2 with placeholder
                            $('#optionvalue').select2({
                                placeholder: $('#optionvalue').data('placeholder') || '{{ __('placeholder.lbl_select_value') }}',
                                allowClear: true,
                                dropdownParent: $('#addModal'),
                                minimumResultsForSearch: 0,
                                width: '100%'
                            });

                            // Listen for when all tags are removed
                            $('#optionvalue').off('select2:unselect').on('select2:unselect', function() {
                                var selectedValues = $(this).val();
                                if (!selectedValues || selectedValues.length === 0) {
                                    // Force placeholder to show by clearing the value
                                    $(this).val(null).trigger('change');
                                }
                            });

                            if (data.value) {
                            // Set the selected options based on the array of IDs
                            var selectedValues = JSON.parse(data
                                .value); // Convert the JSON string to an array

                            // Use AJAX to get the options based on the type
                            $.ajax({
                                url: '{{ route('backend.mobile-setting.get-type-value', ':slug') }}'
                                    .replace(':slug', data.slug),
                                method: 'GET',
                                data: {
                                    type: data.type ? data.type : ''
                                },
                                success: function(response) {
                                    var options =
                                        '<option value="">{{ __('messages.select_an_option') }}</option>'; // Add a default empty option
                                    $.each(response, function(index, item) {
                                        options += '<option value="' + item
                                            .id + '">' + item.name +
                                            '</option>';
                                    });
                                    // Destroy and reinitialize Select2 to ensure placeholder works
                                    if ($('#optionvalue').hasClass('select2-hidden-accessible')) {
                                        $('#optionvalue').select2('destroy');
                                    }

                                    $('#optionvalue').html(options);

                                    // Reinitialize Select2 with placeholder

                                    $('#optionvalue').select2({
                                        placeholder: $('#optionvalue').data('placeholder') || '{{ __('placeholder.lbl_select_value') }}',
                                        allowClear: true,
                                        dropdownParent: $('#addModal'),
                                        minimumResultsForSearch: 0,
                                        width: '100%'
                                    });


                                    // Check if search box exists after reinitialization
                                    setTimeout(function() {
                                        var searchBox = $('#optionvalue').next('.select2-container').find('.select2-search__field');

                                        if (searchBox.length > 0) {

                                        }
                                    }, 100);

                                    // Listen for when all tags are removed
                                    $('#optionvalue').off('select2:unselect').on('select2:unselect', function() {
                                        var selectedValues = $(this).val();
                                        if (!selectedValues || selectedValues.length === 0) {
                                            // Force placeholder to show by clearing the value
                                            $(this).val(null).trigger('change');
                                        }
                                    });

                                    // Set the selected values
                                    $('#optionvalue').val(selectedValues).trigger('change');
                                },
                                error: function(xhr) {
                                    console.error('Error fetching data:', xhr);
                                }
                            });
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.delete-button', function() {
                var id = $(this).data('id');
                var deleteUrl = '{{ route('backend.mobile-setting.destroy', 999) }}';
                deleteUrl = deleteUrl.replace('999', id);
                const fallbackName = {!! json_encode(__($module_title)) !!};
                const rawName = $(this).data('name') || fallbackName;
                const formattedName = (rawName || '').trim();
                const deleteTitle = `Are you sure you want to delete this ${formattedName} list?`;
                const irreversibleText = {!! json_encode(__('messages.you_wont_be_able_to_revert')) !!};

                Swal.fire({
    title: deleteTitle,
    text: irreversibleText,

    icon: 'warning',
    iconHtml: '<i class="fa fa-trash" style="color:#A52A2A;"></i>',

    showCancelButton: true,
    confirmButtonColor: '#A52A2A',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '{{ __('messages.yes_delete_it') }}',
    reverseButtons: true,
}).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteUrl,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(data) {
                                Swal.fire({
                                    title: '{{ __('messages.done') }}',
                                    text: data.message,
                                    icon: 'success',
                                    iconColor: '#5F60B9'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                            }
                        });
                    }
                });
            });

            $('.accordion-btn').on('click', function() {
                var mobileSettingId = $(this).data('id');
                var type = $(this).data('type');
                var dropdown = $('#dashboard_select_' + mobileSettingId);

                $.ajax({
                    url: '{{ route('backend.mobile-setting.get-dropdown-value', ':id') }}'.replace(
                        ':id', mobileSettingId),
                    method: 'GET',
                    data: {
                        type: type ? type : ''
                    },
                    success: function(data) {
                        // Destroy Select2 if already initialized
                        if (dropdown.hasClass('select2-hidden-accessible')) {
                            dropdown.select2('destroy');
                        }

                        dropdown.empty();

                        if (data.selected) {
                            $.each(data.selected, function(key, value) {
                                dropdown.append($('<option>', {
                                    value: value.id,
                                    text: value.name,
                                    selected: true
                                }));
                            });
                        }

                        if (data.available) {
                            $.each(data.available, function(key, value) {
                                if (!data.selected || !data.selected.some(
                                        selectedItem => selectedItem.id === value.id)) {
                                    dropdown.append($('<option>', {
                                        value: value.id,
                                        text: value.name
                                    }));
                                }
                            });
                        }

                        // Initialize Select2 with placeholder

                        dropdown.select2({
                            placeholder: dropdown.data('placeholder') || '{{ __('placeholder.lbl_select_value') }}',
                            allowClear: true,
                            minimumResultsForSearch: 0,
                            width: '100%'
                        });


                        // Check if search box exists after initialization
                        setTimeout(function() {
                            var searchBox = dropdown.next('.select2-container').find('.select2-search__field');

                            if (searchBox.length > 0) {

                            }
                        }, 100);

                        // Set selected values and trigger change
                        if (data.selected && data.selected.length > 0) {
                            var selectedIds = data.selected.map(function(item) { return item.id; });
                            dropdown.val(selectedIds).trigger('change');
                        } else {
                            // If no selections, ensure placeholder shows
                            dropdown.val(null).trigger('change');
                        }

                        // Listen for when all tags are removed
                        dropdown.on('select2:unselect', function() {
                            var selectedValues = dropdown.val();
                            if (!selectedValues || selectedValues.length === 0) {
                                // Force placeholder to show by clearing the value
                                dropdown.val(null).trigger('change');
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to fetch dropdown values:', error);
                    }
                });
            });

            $('.status-switch').on('change', function() {
                var value = $(this).is(':checked') ? 1 : 0;
                var id = $(this).data('id');
                var name = $(this).data('name');
                var position = $(this).data('position');

                $.ajax({
                    url: '{{ route('backend.mobile-setting.store') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        name: name,
                        position: position,
                        value: value
                    },
                    success: function(response) {
                        showMessage(response.message);
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to update status:', error);
                    }
                });
            });
        });


        document.addEventListener('DOMContentLoaded', (event) => {
            let draggedElement = null;

            document.querySelectorAll('.drag-button').forEach(button => {
                button.addEventListener('mousedown', (e) => {
                    const row = document.querySelector(`[data-id="${button.dataset.id}"]`);
                    if (row.dataset.slug === 'banner' || row.dataset.slug === 'continue-watching') {
                        return;
                    }

                    row.setAttribute('draggable', 'true');

                    row.addEventListener('dragstart', (e) => {
                        draggedElement = row;
                        e.dataTransfer.effectAllowed = 'move';
                        row.classList.add('dragging');
                    });

                    row.addEventListener('dragend', (e) => {
                        row.classList.remove('dragging');
                        row.removeAttribute('draggable');
                        updatePositions();
                        // showMessage('Position changed successfully');
                    }, {
                        once: true
                    });

                    row.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';

                        const rows = [...document.querySelectorAll(
                            '.mobile-setting-row:not(.dragging)')];
                        let afterElement = getDragAfterElement(rows, e.clientY);

                        const parent = document.getElementById('sortable');
                        if (afterElement === null) {
                            // Append to the end if no after element
                            parent.appendChild(draggedElement);
                        } else {
                            parent.insertBefore(draggedElement, afterElement);
                        }
                    });

                    row.addEventListener('drop', (e) => {
                        showMessage('{{ __('messages.position_changed_successfully') }}');
                        e.stopPropagation();
                        e.preventDefault();
                    });
                });
            });

            function getDragAfterElement(rows, y) {
                let closest = null;
                let closestOffset = Number.NEGATIVE_INFINITY;

                rows.forEach(row => {
                    const box = row.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;

                    if (offset < 0 && offset > closestOffset) {
                        closestOffset = offset;
                        closest = row;
                    }
                });

                return closest;
            }

            function updatePositions() {
                const rows = document.querySelectorAll('.mobile-setting-row');
                let sortedIDs = [];

                rows.forEach((row, index) => {
                    row.setAttribute('data-position', index + 1);
                    sortedIDs.push(row.getAttribute('data-id'));

                    const positionNumberElement = row.querySelector('.position-number');
                    if (positionNumberElement) {
                        positionNumberElement.textContent =
                            `{{ __('settings.lbl_position_number') }}: ${index + 1}`;
                    }
                });

                $.ajax({
                    url: '{{ route('backend.mobile-setting.update-position') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        sortedIDs: sortedIDs
                    },
                    success: function(response) {

                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to update positions:', error);
                    }
                });
            }
        });

        $('#type').on('change', function() {
            var selectedValue = $(this).val();
            toggleTypeValue();

            if (!selectedValue) {
                $('#tvshow_id_error').text('{{ __('messages.type_field_required') }}');
                return;
            }

            if (selectedValue !== 'advertisement' || selectedValue !== 'rate-our-app') {

                $.ajax({
                    url: '{{ route('backend.mobile-setting.get-type-value', ':slug') }}'.replace(':slug',
                        selectedValue),
                    method: 'GET',
                    data: {
                        type: selectedValue ? selectedValue : ''
                    },

                    success: function(response) {
                        // Destroy and reinitialize Select2 to ensure placeholder works
                        if ($('#optionvalue').hasClass('select2-hidden-accessible')) {
                            $('#optionvalue').select2('destroy');
                        }

                        var options = '';
                        $.each(response, function(index, item) {
                            options += '<option value="' + item.id + '">' + item.name +
                                '</option>';
                        });
                        $('#optionvalue').html(options);

                        // Reinitialize Select2 with placeholder

                        $('#optionvalue').select2({
                            placeholder: $('#optionvalue').data('placeholder') || '{{ __('placeholder.lbl_select_value') }}',
                            allowClear: true,
                            dropdownParent: $('#addModal'),
                            minimumResultsForSearch: 0,
                            width: '100%'
                        }).trigger('change');

                        // Check if search box exists after reinitialization
                        setTimeout(function() {
                            var searchBox = $('#optionvalue').next('.select2-container').find('.select2-search__field');

                            if (searchBox.length > 0) {

                            }
                        }, 100);

                        // Listen for when all tags are removed
                        $('#optionvalue').off('select2:unselect').on('select2:unselect', function() {
                            var selectedValues = $(this).val();
                            if (!selectedValues || selectedValues.length === 0) {
                                // Force placeholder to show by clearing the value
                                $(this).val(null).trigger('change');
                            }
                        });
                    },
                    error: function(xhr) {
                        console.error('Error fetching data:', xhr);
                    }
                });
            }
        });

        function toggleTypeValue() {
            var selectedValue = $('#type').val();
            if (selectedValue === 'advertisement' || selectedValue === 'rate-our-app') {
                $('#type_value').hide();
            } else {
                $('#type_value').show();
            }
        }

        toggleTypeValue();

        // Initialize Select2 with placeholder for #optionvalue
        $(document).ready(function() {

            if ($('#optionvalue').length && !$('#optionvalue').hasClass('select2-hidden-accessible')) {

                $('#optionvalue').select2({
                    placeholder: $('#optionvalue').data('placeholder') || '{{ __('placeholder.lbl_select_value') }}',
                    allowClear: true,
                    dropdownParent: $('#addModal'),
                    minimumResultsForSearch: 0,
                    width: '100%'
                });

                // Listen for when all tags are removed
                $('#optionvalue').on('select2:unselect', function() {
                    var selectedValues = $(this).val();
                    if (!selectedValues || selectedValues.length === 0) {
                        // Force placeholder to show by clearing the value
                        $(this).val(null).trigger('change');
                    }
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('section_type');
            const optionValueSelect = $('#optionvalueSection'); // Select2
            const typeValueDiv = document.getElementById('type_value_div');

            const movieList = @json($movieList);
            const tvshowList = @json($tvshowList);
            const videoList = @json($videoList);
            const channelList = @json($channelList);

            // Initialize Select2 with placeholder for #optionvalueSection

            if (optionValueSelect.length && !optionValueSelect.hasClass('select2-hidden-accessible')) {

                optionValueSelect.select2({
                    placeholder: optionValueSelect.data('placeholder') || '{{ __('placeholder.lbl_select_value') }}',
                    allowClear: true,
                    dropdownParent: $('#addModalSection'),
                    minimumResultsForSearch: 0,
                    width: '100%'
                });


                // Listen for when all tags are removed
                optionValueSelect.on('select2:unselect', function() {
                    var selectedValues = optionValueSelect.val();
                    if (!selectedValues || selectedValues.length === 0) {
                        // Force placeholder to show by clearing the value
                        optionValueSelect.val(null).trigger('change');
                    }
                });
            }

            function populateOptions(list) {
                // Destroy and reinitialize Select2 to ensure placeholder works
                if (optionValueSelect.hasClass('select2-hidden-accessible')) {
                    optionValueSelect.select2('destroy');
                }

                optionValueSelect.empty(); // Clear previous options
                for (const key in list) {
                    optionValueSelect.append(new Option(list[key], key));
                }

                // Reinitialize Select2 with placeholder

                optionValueSelect.select2({
                    placeholder: optionValueSelect.data('placeholder') || '{{ __('placeholder.lbl_select_value') }}',
                    allowClear: true,
                    dropdownParent: $('#addModalSection'),
                    minimumResultsForSearch: 0,
                    width: '100%'
                }).trigger('change'); // Refresh select2


                // Listen for when all tags are removed
                optionValueSelect.off('select2:unselect').on('select2:unselect', function() {
                    var selectedValues = optionValueSelect.val();
                    if (!selectedValues || selectedValues.length === 0) {
                        // Force placeholder to show by clearing the value
                        optionValueSelect.val(null).trigger('change');
                    }
                });
            }

            $('#section_type').on('change', function() {
                const type = this.value;
                if (type === 'movie') {
                    populateOptions(movieList);
                } else if (type === 'tvshow') {
                    populateOptions(tvshowList);
                } else if (type === 'video') {
                    populateOptions(videoList);
                } else if (type === 'channel') {
                    populateOptions(channelList);
                }
            });

            // Trigger change if old value exists
            if (typeSelect.value) {
                typeSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
@endpush
