@extends('backend.layouts.app')
@section('content')
<x-back-button-component route="backend.seasons.index" />
<p class="text-danger" id="error_message"></p>
@if(isenablemodule('enable_tmdb_api')==1)

<div class="mb-3">

    <div class="d-flex flex-md-row flex-column align-items-end justify-content-between gap-3 mb-3">
        <div class="flex-grow-1 w-100">
            <div class="row">
                {{ html()->label(__('movie.import_season'))->class('form-label') }}
                <div class="col-md-6">
                    {{ html()->label(__('movie.tvshows'), 'tvshows')->class('form-label') }}
                    {{ html()->select(
                        'tv_show_id',
                        $imported_tvshow->pluck('name', 'tmdb_id')->prepend(__('placeholder.lbl_select_tvshow'), null),

                    )->class('form-control select2')->id('tv_show_id') }}

                    <span class="text-danger" id="tvshow_id_error"></span>
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('movie.seasons'), 'seasons')->class('form-label') }}
                    {{ html()->select(
                        'season_id',
                        null,

                    )->class('form-control select2')->id('season_id') }}
                    <span class="text-danger" id="season_id_error"></span>
                </div>
            </div>
        </div>
        <div class="flex-shrink-0 d-flex gap-3 align-items-center flex-wrap">
            <div id="loader" style="display: none;">
                <button class="btn btn-md btn-primary">{{__('tvshow.lbl_loading')}}</button>
            </div>
            <button class="btn btn-md btn-primary" id="import_season_id">{{__('tvshow.lbl_import')}}</button>
        </div>
    </div>
</div>
@endif

<div class="d-flex align-items-center justify-content-between mt-5 pt-4 mb-3">
    <h6>{{ __('movie.lbl_season_title') }} </h6>
</div>

{{ html()->form('POST' ,route('backend.seasons.store'))
    ->attribute('enctype', 'multipart/form-data')
    ->attribute('data-toggle', 'validator')
    ->attribute('id', 'form-submit')  // Add the id attribute here
    ->class('requires-validation')  // Add the requires-validation class
    ->attribute('novalidate', 'novalidate')  // Disable default browser validation
    ->open()
}}
<div class="card">
    <div class="card-body">
        <div class="row gy-3">
            {{ html()->hidden('season_index', null)->id('season_index') }}
            {{ html()->hidden('tmdb_id', null)->id('tmdb_id') }}
            <div class="col-md-6 col-lg-3">
                {{ html()->label(__('movie.lbl_poster'), 'poster')->class('form-label form-control-label') }}
                <div class="position-relative">
                    <div class="input-group btn-file-upload">
                        {{ html()->button('<i class="ph ph-image"></i>'. __('messages.lbl_choose_image'))
                            ->class('input-group-text form-control')
                            ->type('button')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainerPoster')
                            ->attribute('data-hidden-input', 'file_url_poster')
                            ->style('height:7.5rem')
                        }}

                        {{ html()->text('poster_input')
                            ->class('form-control')
                            ->placeholder('Select Image')
                            ->attribute('aria-label', 'Poster Image')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainerPoster')
                            ->attribute('data-hidden-input', 'file_url_poster')
                        }}
                    </div>
                    <div class="uploaded-image" id="selectedImageContainerPoster">

                        <img id="selectedPosterImage"
                        src="{{ old('poster_url', isset($data) ? $data->poster_url : '') }}" alt="feature-image"
                        class="img-fluid mb-2 avatar-80 "
                        style="{{ old('poster_url', isset($data) ? $data->poster_url : '') ? '' : 'display:none;' }}" />

                        {{-- @if(old('poster_url', isset($data) ? $data->poster_url : '')) --}}
                            {{-- <img id="selectedPosterImage" src="{{ old('poster_url', isset($data) ? $data->poster_url : '') }}" class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;"> --}}
                        {{-- @endif --}}
                    </div>
                    {{ html()->hidden('poster_url')->id('file_url_poster')->value(old('poster_url', isset($data) ? $data->poster_url : '')) }}
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                {{ html()->label(__('movie.lbl_poster_tv'), 'poster_tv')->class('form-label form-control-label') }}
                <div class="position-relative">
                    <div class="input-group btn-file-upload">
                        {{ html()->button('<i class="ph ph-image"></i>'. __('messages.lbl_choose_image'))
                            ->class('input-group-text form-control')
                            ->type('button')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainerPosterTv')
                            ->attribute('data-hidden-input', 'file_url_poster_tv')
                            ->style('height:7.5rem')
                        }}

                        {{ html()->text('poster_input')
                            ->class('form-control')
                            ->placeholder('Select Image')
                            ->attribute('aria-label', 'Poster Image')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainerPosterTv')
                            ->attribute('data-hidden-input', 'file_url_poster_tv')
                        }}
                    </div>
                    <div class="uploaded-image" id="selectedImageContainerPosterTv">

                        <img id="selectedPosterTvImage"
                        src="{{ old('poster_tv_url', isset($data) ? $data->poster_tv_url : '') }}" alt="feature-image"
                        class="img-fluid mb-2 avatar-80 "
                        style="{{ old('poster_tv_url', isset($data) ? $data->poster_tv_url : '') ? '' : 'display:none;' }}" />


                    </div>
                    {{ html()->hidden('poster_tv_url')->id('file_url_poster_tv')->value(old('poster_tv_url', isset($data) ? $data->poster_tv_url : '')) }}
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="mb-3">
                    {{ html()->label(__('movie.lbl_name') . ' <span class="text-danger">*</span>', 'name')->class('form-label form-control-label') }}
                    {{ html()->text('name')->attribute('value', old('name'))->placeholder(__('placeholder.lbl_season_name'))->class('form-control')->attribute('required','required') }}
                    <span class="text-danger" id="error_msg"></span>
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="name-error">Name field is required</div>
                </div>
                <div>
                    {{ html()->label(__('season.lbl_tv_shows') . ' <span class="text-danger">*</span>', 'type')->class('form-label') }}
                    {{ html()->select(
                            'entertainment_id',
                            $tvshows->pluck('name', 'id')->prepend(__('placeholder.lbl_select_tvshow'), old('entertainment_id')),

                        )->class('form-control select2')->id('entertainment_id')->attribute('required','required') }}
                    @error('entertainment_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="name-error">TV Show field is required</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="mb-3">
                    {{ html()->label(__('movie.lbl_trailer_url_type').' <span class="text-danger">*</span>', 'type')->class('form-label') }}
                    {{ html()->select(
                            'trailer_url_type',
                            $upload_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_type'), ''),
                            old('trailer_url_type', ''), // Set '' as the default value
                        )->class('form-control select2')->id('trailer_url_type') }}
                    @error('trailer_url_type')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="name-error">Trailer Type field is required</div>

                </div>
                <div class="d-none" id="url_input">
                    {{ html()->label(__('movie.lbl_trailer_url').' <span class="text-danger">*</span>', 'trailer_url')->class('form-label form-control-label') }}
                    {{ html()->text('trailer_url')->attribute('value', old('trailer_url'))->placeholder(__('placeholder.lbl_trailer_url'))->class('form-control') }}
                    @error('trailer_url')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="trailer-url-error">Video URL field is required</div>
                    <div class="invalid-feedback" id="trailer-pattern-error" style="display:none;">
                    Please enter a valid URL starting with http:// or https://.
                </div>
                </div>
                <div class="d-none" id="embed_input">
    {{ html()->label(__('movie.lbl_embed_code').' <span class="text-danger">*</span>', 'trailer_embedded')->class('form-label form-control-label') }}
    {{ html()->textarea('trailer_embedded')
        ->attribute('value', old('trailer_embedded'))
        ->placeholder('<iframe ...></iframe>')
        ->class('form-control')
        ->rows(4) }}
    @error('trailer_embedded')
        <span class="text-danger">{{ $message }}</span>
    @enderror
    <div class="invalid-feedback" id="trailer-iframe-error">Iframe embed code is required</div>
</div>
                <div class="d-none" id="url_file_input">
                    {{ html()->label(__('movie.lbl_trailer_video').' <span class="text-danger">*</span>', 'trailer_video')->class('form-label form-control-label') }}

                    <div class="input-group btn-video-link-upload">
                        {{ html()->button(__('placeholder.lbl_select_file').'<i class="ph ph-upload"></i>')
                            ->class('input-group-text form-control')
                            ->type('button')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainertailerurl')
                            ->attribute('data-hidden-input', 'file_url_trailer')
                        }}

                        {{ html()->text('trailer_input')
                            ->class('form-control')
                            ->placeholder('Select Image')
                            ->attribute('aria-label', 'Trailer Image')
                            ->attribute('data-bs-toggle', 'modal')
                            ->attribute('data-bs-target', '#exampleModal')
                            ->attribute('data-image-container', 'selectedImageContainertailerurl')
                            ->attribute('data-hidden-input', 'file_url_trailer')
                        }}
                    </div>

                    <div class="mt-3" id="selectedImageContainertailerurl">
                        @if(old('trailer_url', isset($data) ? $data->trailer_url : ''))
                            <img src="{{ old('trailer_url', isset($data) ? $data->trailer_url : '') }}" class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;">
                        @endif
                    </div>

                    {{ html()->hidden('trailer_video')->id('file_url_trailer')->value(old('trailer_url', isset($data) ? $data->trailer_url : ''))->attribute('data-validation', 'iq_video_quality')  }}
                    {{-- {{ html()->file('trailer_video')->class('form-control-file')->accept('video/*')->class('form-control') }} --}}

                    @error('trailer_video')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="trailer-file-error">Video File field is required</div>

                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                {{ html()->label(__('movie.lbl_movie_access') , 'access')->class('form-label form-control-label') }}
                <div class="d-flex align-items-center">
                    <label class="form-check form-check-inline form-control px-5 cursor-pointer">
                    <div>
                        <input class="form-check-input" type="radio" name="access" id="paid" value="paid"
                            onchange="showPlanSelection(this.value === 'paid')"
                            {{ old('access') == 'paid' ? 'checked' : '' }} checked>
                        <span class="form-check-label" >{{__('movie.lbl_paid')}}</span>
                    </div>
                </label>
                <label class="form-check form-check-inline form-control px-5 cursor-pointer">
                    <div >
                        <input class="form-check-input" type="radio" name="access" id="free" value="free"
                            onchange="showPlanSelection(this.value === 'paid')"
                            {{ old('access') == 'free' ? 'checked' : '' }}>
                        <span class="form-check-label" for="free">{{__('movie.lbl_free')}}</span>
                    </div>
                </label>
                {{-- <label class="form-check form-check-inline form-control px-5 cursor-pointer">
                    <div>
                        <input class="form-check-input" type="radio" name="access" id="pay-per-view" value="pay-per-view"
                            onchange="showPlanSelection(this.value === 'pay-per-view')"
                            {{ old('access') == 'pay-per-view' ? 'checked' : '' }} >
                        <span class="form-check-label" for="pay-per-view">{{__('messages.lbl_pay_per_view')}}</span>
                    </div>
                </label> --}}
                </div>
                @error('access')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-12 row g-3 mt-2 {{ old('access') == 'pay-per-view' ? '' : 'd-none' }}" id="payPerViewFields">
                <div class="col-md-4">
                    {{ html()->label(__('messages.lbl_price') . '<span class="text-danger">*</span>', 'price')->class('form-label')->for('price') }}
                    {{ html()->number('price', old('price'))->class('form-control')->attribute('placeholder', __('messages.enter_price'))->required() }}
                    @error('price') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="invalid-feedback" id="price-error">Price field is required</div>
                <div class="col-md-4">
                    {{ html()->label(__('messages.purchase_type'), 'access_duration')->class('form-label') }}
                    {{ html()->select('purchase_type', [
                            '' => __('messages.lbl_select_purchase_type'),
                            'rental' => __('messages.lbl_rental'),
                            'onetime' => __('messages.lbl_one_time_purchase')
                        ], old('purchase_type', 'rental'))
                        ->id('purchase_type')
                        ->class('form-control select2')
                        ->attributes(['onchange' => 'toggleAccessDuration(this.value)'])
                    }}
                </div>
                <div class="col-md-4 d-none" id="accessDurationWrapper">
                    {{ html()->label(__('messages.lbl_access_duration') . __('messages.lbl_in_days'), 'access_duration')->class('form-label') }}
                    {{ html()->number('access_duration', old('access_duration'))->class('form-control')->attribute('placeholder', __('messages.access_duration')) }}
                    @error('access_duration') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-4">
                    {{ html()->label(__('messages.lbl_discount') . ' (%)', 'discount')->class('form-label') }}
                    {{ html()->number('discount', old('discount'))->class('form-control')->attribute('placeholder', __('messages.enter_discount'))->attribute('min', 1)->attribute('max', 99) }}
                    @error('discount') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    {{ html()->label(__('messages.lbl_total_price'), 'total_amount')->class('form-label') }}
                    {{ html()->text('total_amount', null)->class('form-control')->attribute('disabled', true)->id('total_amount') }}
                </div>
                <div class="col-md-4">
                    {{ html()->label(__('messages.lbl_available_for') . __('messages.lbl_in_days'), 'available_for')->class('form-label') }}
                    {{ html()->number('available_for', old('available_for'))->class('form-control')->attribute('placeholder', __('messages.available_for')) }}
                    @error('available_for') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="col-md-6 col-lg-4 {{ old('access', 'paid') == 'free' ? 'd-none' : '' }}" id="planSelection">
                {{ html()->label(__('movie.lbl_select_plan'). ' <span class="text-danger">*</span>', 'type')->class('form-label') }}
                {{ html()->select('plan_id', $plan->pluck('name', 'id')->prepend(__('placeholder.lbl_select_plan'), ''), old('plan_id'))->class('form-control select2')->id('plan_id') }}

                @error('plan_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="name-error">Plan field is required</div>
            </div>
            <div class="col-md-6 col-lg-4">
                {{ html()->label(__('plan.lbl_status'), 'status')->class('form-label') }}
                <div class="d-flex justify-content-between align-items-center form-control">
                    {{ html()->label(__('messages.active'), 'status')->class('form-label text-body mb-0') }}
                    <div class="form-check form-switch">
                        {{ html()->hidden('status', 0) }}
                        {{
                            html()->checkbox('status', old('status', 1))
                                ->class('form-check-input')
                                ->id('status')
                                ->value(1)
                        }}
                    </div>
                </div>
                @error('status')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-lg-6">

                <div class="d-flex align-items-center justify-content-between mb-2">
                    {{ html()->label(__('movie.lbl_short_desc'), 'short_desc')->class('form-label') }}
                    <span class="text-primary cursor-pointer" id="GenrateshortDescription" ><i class="ph ph-info" data-bs-toggle="tooltip" title="{{ __('messages.chatgpt_info') }}"></i> {{ __('messages.lbl_chatgpt') }}</span>
                </div>

                {{ html()->textarea('short_desc', old('short_desc'))->class('form-control')->id('short_desc')->placeholder(__('placeholder.lbl_season_short_desc'))->rows('8') }}
                @error('short_desc')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    {{ html()->label(__('movie.lbl_description'). '<span class="text-danger"> *</span>', 'description')->class('form-label mb-0') }}
                    <span class="text-primary cursor-pointer" id="GenrateDescription" ><i class="ph ph-info" data-bs-toggle="tooltip" title="{{ __('messages.chatgpt_info') }}"></i> {{ __('messages.lbl_chatgpt') }}</span>
                </div>
                {{ html()->textarea('description', old('description'))->class('form-control')->id('description')->placeholder(__('placeholder.lbl_movie_description'))->rows(4)->attribute('required','required') }}
                @error('description')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="desc-error">Description field is required</div>
            </div>
        </div>
    </div>
</div>

<div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">
    {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button-video') }}
</div>

{{ html()->form()->close() }}
@include('components.media-modal')
@endsection
@push('after-scripts')
<script>

tinymce.init({
    selector: '#description',
    plugins: 'link image code',
    toolbar: 'undo redo | styleselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify | removeformat | code | image',
    setup: function(editor) {
                // Setup TinyMCE to listen for changes
                editor.on('change', function(e) {
                    // Get the editor content
                    const content = editor.getContent().trim();
                    const $textarea = $('#description');
                    const $error = $('#desc-error');

                    // Check if content is empty
                    if (content === '') {
                        $textarea.addClass('is-invalid'); // Add invalid class if empty
                        $error.show(); // Show validation message

                    } else {
                        $textarea.removeClass('is-invalid'); // Remove invalid class if not empty
                        $error.hide(); // Hide validation message
                    }
                });
            }
});

$(document).on('click', '.variable_button', function() {
    const textarea = $(document).find('.tab-pane.active');
    const textareaID = textarea.find('textarea').attr('id');
    tinyMCE.activeEditor.selection.setContent($(this).attr('data-value'));
});


        document.addEventListener('DOMContentLoaded', function() {

            function handleTrailerUrlTypeChange(selectedValue) {
                var FileInput = document.getElementById('url_file_input');
                var URLInput = document.getElementById('url_input');
                var EmbedInput = document.getElementById('embed_input');
                var trailerfile = document.querySelector('input[name="trailer_video"]');
                var trailerfileError = document.getElementById('trailer-file-error');
                var urlError = document.getElementById('trailer-url-error');
                var URLInputField = document.querySelector('input[name="trailer_url"]');
                var IframeField = document.querySelector('textarea[name="trailer_iframe"]');

                // Hide all inputs first
                FileInput.classList.add('d-none');
                URLInput.classList.add('d-none');
                EmbedInput.classList.add('d-none');

                // Remove all required attributes
                URLInputField?.removeAttribute('required');
                trailerfile?.removeAttribute('required');
                IframeField?.removeAttribute('required');

                if (selectedValue === 'Local') {
                    FileInput.classList.remove('d-none');
                    trailerfile?.setAttribute('required', 'required');
                } else if (selectedValue === 'Embedded') {
                    EmbedInput.classList.remove('d-none');
                    IframeField?.setAttribute('required', 'required');
                } else if (selectedValue === 'URL' || selectedValue === 'YouTube' || selectedValue === 'HLS' ||
                           selectedValue === 'x265' || selectedValue === 'Vimeo') {
                    URLInput.classList.remove('d-none');
                    URLInputField?.setAttribute('required', 'required');
                    validateTrailerUrlInput();
                }
            }

            function validateTrailerUrlInput() {
                    var URLInput = document.querySelector('input[name="trailer_url"]');
                    var urlPatternError = document.getElementById('trailer-pattern-error');
                    selectedValue = document.getElementById('trailer_url_type').value;
                    if (selectedValue === 'YouTube') {
                        urlPattern = /^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/;
                        urlPatternError.innerText = '';
                        urlPatternError.innerText='Please enter a valid Youtube URL'
                    } else if (selectedValue === 'Vimeo') {
                        urlPattern = /^(https?:\/\/)?(www\.vimeo\.com)\/.+$/;
                        urlPatternError.innerText = '';
                        urlPatternError.innerText='Please enter a valid Vimeo URL'
                    } else {
                        // General URL pattern for other types
                        urlPattern = /^https?:\/\/.+$/;
                         urlPatternError.innerText='Please enter a valid URL'
                    }
                        if (!urlPattern.test(URLInput.value)) {
                            urlPatternError.style.display = 'block';
                            return false;
                        } else {
                            urlPatternError.style.display = 'none';
                            return true;
                        }
                    }
            var initialSelectedValue = document.getElementById('trailer_url_type').value;
            handleTrailerUrlTypeChange(initialSelectedValue);
            $('#trailer_url_type').change(function() {
                var selectedValue = $(this).val();
                handleTrailerUrlTypeChange(selectedValue);
            });


            var URLInput = document.querySelector('input[name="trailer_url"]');
                if (URLInput) {
                    URLInput.addEventListener('input', function() {
                        validateTrailerUrlInput();
                    });
                }
        });

        function showPlanSelection() {
                const planSelection = document.getElementById('planSelection');
                const payPerViewFields = document.getElementById('payPerViewFields');
                const planIdSelect = document.getElementById('plan_id');
                const priceInput = document.querySelector('input[name="price"]');
                const selectedAccess = document.querySelector('input[name="access"]:checked');

                if (!selectedAccess) return;

                const value = selectedAccess.value;

                // Handle visibility and required attributes
                if (value === 'paid') {
                    planSelection.classList.remove('d-none');
                    payPerViewFields.classList.add('d-none');
                    planIdSelect.setAttribute('required', 'required');
                    priceInput.removeAttribute('required');
                } else if (value === 'pay-per-view') {
                    planSelection.classList.add('d-none');
                    payPerViewFields.classList.remove('d-none');
                    planIdSelect.removeAttribute('required');
                    priceInput.setAttribute('required', 'required');
                } else {
                    planSelection.classList.add('d-none');
                    payPerViewFields.classList.add('d-none');
                    planIdSelect.removeAttribute('required');
                    priceInput.removeAttribute('required');
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                // Initial setup
                showPlanSelection();

                // Event listeners for movie access radio buttons
                const accessRadios = document.querySelectorAll('input[name="access"]');
                accessRadios.forEach(function (radio) {
                    radio.addEventListener('change', showPlanSelection);
                });
            });

            function toggleAccessDuration(value) {
                const accessDuration = document.getElementById('accessDurationWrapper');
                accessDuration.classList.toggle('d-none', value !== 'rental');
            }

            document.addEventListener('DOMContentLoaded', function () {
                const purchaseType = document.getElementById('purchase_type');
                if (purchaseType) {
                    toggleAccessDuration(purchaseType.value);
                    purchaseType.addEventListener('change', function () {
                        toggleAccessDuration(this.value);
                    });
                }
            });




      function getSeasons(tmdbId, selectedSeasonId = "") {
                   var get_seasons_list = "{{ route('backend.seasons.import-season-list', ['tmdb_id' => '']) }}" + tmdbId;
                   get_seasons_list = get_seasons_list.replace('amp;', '');

                   $.ajax({
                       url: get_seasons_list,
                       success: function(result) {

                           var formattedResult = result.map(function(season) {
                               return { id: season.season_number, text: season.name };
                           });

                           $('#season_id').select2({
                               width: '100%',
                               placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.season')]) }}",
                               data: formattedResult
                           });

                           if (selectedSeasonId != "") {
                               $('#season_id').val(selectedSeasonId).trigger('change');
                           }
                       }
                   });
                }


            $(document).ready(function() {
             $('#tv_show_id').change(function() {
                var tvShowId = $(this).val();
                if (tvShowId) {
                    $('#season_id').empty().select2({
                        width: '100%',
                        placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.season')]) }}"
                    });
                    getSeasons(tvShowId);
                } else {
                    $('#season_id').empty().select2({
                        width: '100%',
                        placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.season')]) }}"
                    });
                }
            });
        });



     $(document).ready(function() {

    $('#import_season_id').on('click', function(e) {
       e.preventDefault();

       var tvshowID = $('#tv_show_id').val();
       $('#tvshow_id_error').text('');
       $('#error_message').text('');


       var seasonID = $('#season_id').val();
       $('#season_id_error').text('');
       $('#error_message').text('');

       var import_season = "{{ route('backend.seasons.import-season') }}";
           import_season = import_season.replace('amp;', '');

       if (!tvshowID) {
           $('#tvshow_id_error').text('TV Show ID is required.');
           return;
       }

       if (!seasonID) {
           $('#season_id_error').text('Season is required.');
           return;
       }

       $('#loader').show();
       $('#import_season_id').hide();

       $.ajax({
           url: import_season,
           type: 'POST',
           headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
           data: {
                   tvshow_id: tvshowID,
                   season_id: seasonID,
                 },
           success: function(response) {

               $('#loader').hide();
               $('#import_season_id').show();

               if(response.success){

                var data = response.data;

                $('#season_index').val(data.season_index);
                $('#tmdb_id').val(data.tvshow_id);
                $('#selectedPosterImage').attr('src', data.poster_url).show();
                $('#selectedPosterTvImage').attr('src', data.poster_url).show();
                $('#name').val(data.name);
                $('#description').val(data.description);
                $('#trailer_url_type').val(data.trailer_url_type).trigger('change');
                $('#trailer_url').val(data.trailer_url);
                $('#file_url_poster').val(data.poster_url);
                $('#file_url_poster_tv').val(data.poster_url);
                $('#entertainment_id').val(data.entertainment_id).trigger('change');

                 if(data.poster_url) {
                      $('#selectedPosterImage').attr('src', data.poster_url).show();
                  }
                  if(data.poster_tv_url) {
                      $('#selectedPosterTvImage').attr('src', data.poster_tv_url).show();
                  }
                  if (data.access === 'paid') {
                    document.getElementById('paid').checked = true;
                    showPlanSelection(true);
                  } else {
                    document.getElementById('free').checked = true;
                    showPlanSelection(false);
                  }

               } else {
                   $('#error_message').text(response.message || 'Failed to import movie details.');
               }
           },
           error: function(xhr) {
            $('#loader').hide();
               $('#import_season_id').show();

               $('#loader').hide();
               $('#import_movie').show();
               if (xhr.responseJSON && xhr.responseJSON.message) {
                   $('#error_message').text(xhr.responseJSON.message);
               } else {
                   $('#error_message').text('An error occurred while fetching the movie details.');
               }
            }
          });
      });
  });

  $(document).ready(function() {

$('#GenrateshortDescription').on('click', function(e) {

    e.preventDefault();

    var description = $('#short_desc').val();
    var name = $('#name').val();
    var tvshow = $('#entertainment_id').val();
    var type='short_desc';

    var generate_discription = "{{ route('backend.seasons.generate-description') }}";
        generate_discription = generate_discription.replace('amp;', '');

    if (!description && !name) {
        // $('#error_msg').text('Name field is required');
         return;
     }

     $('#short_desc').text('Loading...')


  $.ajax({

       url: generate_discription,
       type: 'POST',
       headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
       data: {
               description: description,
               name: name,
               tvshow: tvshow,
               type:type
             },
       success: function(response) {

           $('#short_desc').text('')

            if(response.success){

             var data = response.data;
             $('#short_desc').html(data)

            } else {
                $('#error_message').text(response.message || 'Failed to get Description.');
            }
        },
       error: function(xhr) {
         $('#error_message').text('Failed to get Description.');
         $('#short_desc').text('');
           if (xhr.responseJSON && xhr.responseJSON.message) {
               $('#error_message').text(xhr.responseJSON.message);
           } else {
               $('#error_message').text('An error occurred while fetching the movie details.');
           }
        }
    });
  });
});

$(document).ready(function() {

$('#GenrateDescription').on('click', function(e) {

    e.preventDefault();

    var description = $('#description').val();
    var name = $('#name').val();
    var tvshow = $('#entertainment_id').val();
    var type='short_desc';


    var generate_discription = "{{ route('backend.seasons.generate-description') }}";
        generate_discription = generate_discription.replace('amp;', '');

    if (!description && !name) {
        $('#error_msg').text('Name field is required');
         return;
     }

    tinymce.get('description').setContent('Loading...');

  $.ajax({

       url: generate_discription,
       type: 'POST',
       headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
       data: {
               description: description,
               name: name,
               tvshow: tvshow,
               type:type
             },
       success: function(response) {

          tinymce.get('description').setContent('');

            if(response.success){

             var data = response.data;

             tinymce.get('description').setContent(data);

            } else {
                $('#error_message').text(response.message || 'Failed to get Description.');
            }
        },
       error: function(xhr) {
         $('#error_message').text('Failed to get Description.');
         tinymce.get('description').setContent('');

           if (xhr.responseJSON && xhr.responseJSON.message) {
               $('#error_message').text(xhr.responseJSON.message);
           } else {
               $('#error_message').text('An error occurred while fetching the movie details.');
           }
        }
    });
 });
});

function calculateTotal() {
                const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
                const discount = parseFloat(document.querySelector('input[name="discount"]').value) || 0;
                let total = price;

                if (discount > 0 && discount < 100) {
                    total = price - ((price * discount) / 100);
                }

                document.getElementById('total_amount').value = total.toFixed(2);
            }

            document.addEventListener('DOMContentLoaded', function () {
                const priceInput = document.querySelector('input[name="price"]');
                const discountInput = document.querySelector('input[name="discount"]');

                priceInput.addEventListener('input', calculateTotal);
                discountInput.addEventListener('input', calculateTotal);

                // Trigger initial calculation if old values exist
                calculateTotal();
            });

      $(document).on('click', '.variable_button', function() {
          const textarea = $(document).find('.tab-pane.active');
          const textareaID = textarea.find('textarea').attr('id');
          tinyMCE.activeEditor.selection.setContent($(this).attr('data-value'));
      });

function validateEmbedInput(inputId, errorId) {
    const embedInput = document.getElementById(inputId);
    const embedError = document.getElementById(errorId);
    const value = embedInput?.value.trim() || '';

    // Error messages from Laravel translations
    const msgRequired = "{{ __('messages.embed_code_required') }}";
    const msgInvalid = "{{ __('messages.embed_code_invalid') }}";
    const msgOnlyYoutubeVimeo = "{{ __('messages.embed_code_only_youtube_vimeo') }}";

    // Clear previous error
    if (embedError) embedError.style.display = 'none';
    if (embedInput) embedInput.classList.remove('is-invalid');

    if (!embedInput || value === '') {
        return showError(msgRequired);
    }

    // Extract iframe src
    const iframeMatch = value.match(/^<iframe[^>]+src=\"([^\"]+)\"[^>]*><\/iframe>$/i);
    if (!iframeMatch) {
        return showError(msgInvalid);
    }

    const src = iframeMatch[1];

    // Accept YouTube/Vimeo embeds with optional query params
    const isValidYouTubeEmbed = /^https:\/\/www\.youtube\.com\/embed\/[A-Za-z0-9_-]+(\?.*)?$/.test(src);
    const isValidVimeoEmbed = /^https:\/\/player\.vimeo\.com\/video\/\d+(\?.*)?$/.test(src);

    if (!isValidYouTubeEmbed && !isValidVimeoEmbed) {
        return showError(msgOnlyYoutubeVimeo);
    }

    return true;

    function showError(message) {
        if (embedError) embedError.innerText = message;
        if (embedError) embedError.style.display = 'block';
        if (embedInput) embedInput.classList.add('is-invalid');
        return false;
    }
}


document.addEventListener('DOMContentLoaded', function () {
    // Live validation
    ['video_embedded', 'trailer_embedded'].forEach((id, i) => {
        const input = document.getElementById(id);
        const errorId = i === 0 ? 'video-embed-error' : 'trailer-iframe-error';
        if (input) {
            input.addEventListener('input', () => validateEmbedInput(id, errorId));
        }
    });

    // Form validation
    const form = document.getElementById('form-submit');
    if (form) {
        form.addEventListener('submit', function (e) {
            const videoType = document.getElementById('video_upload_type')?.value;
            const trailerType = document.getElementById('trailer_url_type')?.value;

            let isValid = true;

            if (trailerType === 'Embedded') {
                isValid = validateEmbedInput('trailer_embedded', 'trailer-iframe-error');
            }

            if (!isValid) {
                e.preventDefault();
                return false;
                $('#submit-button-video').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{ trans("messages.save") }}');

            }             // Only here, after validation passes, set loading/disabled
        });
    }
});

</script>
@endpush
