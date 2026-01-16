@extends('backend.layouts.app')

@section('content')

<x-back-button-component route="backend.tvshows.index" />
<p class="text-danger" id="error_message"></p>
        {{ html()->form('PUT' ,route('backend.entertainments.update', $data->id))
        ->attribute('enctype', 'multipart/form-data')
        ->attribute('data-toggle', 'validator')
        ->attribute('id', 'form-submit')  // Add the id attribute here
        ->class('requires-validation')  // Add the requires-validation class
        ->attribute('novalidate', 'novalidate')  // Disable default browser validation
        ->open()
    }}

        @csrf

        <div class="d-flex align-items-center justify-content-between mt-5 pt-4 mb-3">
            <h6>{{__('customer.about')}} {{__('season.lbl_tv_shows')}}</h6>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="row gy-3">
                    <input type="hidden" name="tmdb_id" id="tmdb_id" value="{{ $tmdb_id }}">
                    {{ html()->hidden('type', $data->type)->id('type') }}
                    <div class="col-md-4 col-lg-4">
                        <div class="position-relative">
                            {{ html()->label(__('movie.lbl_thumbnail'), 'thumbnail')->class('form-label') }}
                                <div class="input-group btn-file-upload">
                                    {{ html()->button(__('<i class="ph ph-image"></i>'. __('messages.lbl_choose_image')))
                                        ->class('input-group-text form-control')
                                        ->type('button')
                                        ->attribute('data-bs-toggle', 'modal')
                                        ->attribute('data-bs-target', '#exampleModal')
                                        ->attribute('data-image-container', 'selectedImageContainer1')
                                        ->attribute('data-hidden-input', 'file_url1')
                                        ->style('height:13rem')
                                    }}

                                    {{ html()->text('image_input1')
                                        ->class('form-control')
                                        ->placeholder(__('placeholder.lbl_image'))
                                        ->attribute('aria-label', 'Image Input 1')
                                        ->attribute('data-bs-toggle', 'modal')
                                        ->attribute('data-bs-target', '#exampleModal')
                                        ->attribute('data-image-container', 'selectedImageContainer1')
                                        ->attribute('data-hidden-input', 'file_url1')
                                        ->attribute('aria-describedby', 'basic-addon1')
                                    }}
                                </div>
                                <div class="uploaded-image" id="selectedImageContainer1">
                                    @if ($data->thumbnail_url)
                                        <img src="{{ $data->thumbnail_url }}" class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;">
                                        <span class="remove-media-icon"
                                              style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                                              onclick="removeThumbnail('file_url1', 'remove_image_flag_thumbnail')">×</span>
                                    @endif
                                </div>
                                {{ html()->hidden('thumbnail_url')->id('file_url1')->value($data->thumbnail_url) }}
                                {{ html()->hidden('remove_image_thumbnail')->id('remove_image_flag_thumbnail')->value(0) }}
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-4">
                        <div class="position-relative">
                            {{ html()->label(__('movie.lbl_poster'), 'poster')->class('form-label form-control-label') }}
                            <div class="input-group btn-file-upload mb-3">
                                {{ html()->button('<i class="ph ph-image"></i>'. __('messages.lbl_choose_image'))
                                    ->class('input-group-text form-control')
                                    ->type('button')
                                    ->attribute('data-bs-toggle', 'modal')
                                    ->attribute('data-bs-target', '#exampleModal')
                                    ->attribute('data-image-container', 'selectedImageContainer2')
                                    ->attribute('data-hidden-input', 'file_url2')
                                    ->style('height:13rem')
                                }}

                                {{ html()->text('poster_input')
                                    ->class('form-control')
                                    ->placeholder('Select Image')
                                    ->attribute('aria-label', 'Image Input 2')
                                    ->attribute('data-bs-toggle', 'modal')
                                    ->attribute('data-bs-target', '#exampleModal')
                                    ->attribute('data-image-container', 'selectedImageContainer2')
                                    ->attribute('data-hidden-input', 'file_url2')
                                    ->attribute('aria-describedby', 'basic-addon1')
                                }}
                            </div>

                            <div class="uploaded-image" id="selectedImageContainer2">
                                @if ($data->poster_url)
                                <img src="{{ $data->poster_url }}" class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;">
                                    <span class="remove-media-icon"
                                          style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                                          onclick="removeImage('file_url2', 'remove_image_flag')">×</span>
                                @endif
                            </div>
                            {{ html()->hidden('poster_url')->id('file_url2')->value($data->poster_url) }}
                            {{ html()->hidden('remove_image')->id('remove_image_flag')->value(0) }}
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-4">
                        <div class="position-relative">
                            {{ html()->label(__('movie.lbl_poster_tv'), 'poster_tv')->class('form-label form-control-label') }}
                            <div class="input-group btn-file-upload mb-3">
                                {{ html()->button('<i class="ph ph-image"></i>'. __('messages.lbl_choose_image'))
                                    ->class('input-group-text form-control')
                                    ->type('button')
                                    ->attribute('data-bs-toggle', 'modal')
                                    ->attribute('data-bs-target', '#exampleModal')
                                    ->attribute('data-image-container', 'selectedImageContainertv')
                                    ->attribute('data-hidden-input', 'file_url3')
                                    ->style('height:13rem')
                                }}

                                {{ html()->text('poster_input')
                                    ->class('form-control')
                                    ->placeholder('Select Image')
                                    ->attribute('aria-label', 'Image Input 3')
                                    ->attribute('data-bs-toggle', 'modal')
                                    ->attribute('data-bs-target', '#exampleModal')
                                    ->attribute('data-image-container', 'selectedImageContainertv')
                                    ->attribute('data-hidden-input', 'file_url3')
                                    ->attribute('aria-describedby', 'basic-addon1')
                                }}
                            </div>

                            <div class="uploaded-image" id="selectedImageContainertv">
                                @if ($data->poster_tv_url)
                                <img src="{{ $data->poster_tv_url }}" class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;">
                                    <span class="remove-media-icon"
                                          style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                                          onclick="removeTvImage('file_url3', 'remove_image_flag_tv')">×</span>
                                @endif
                            </div>
                            {{ html()->hidden('poster_tv_url')->id('file_url3')->value($data->poster_tv_url) }}
                            {{ html()->hidden('remove_image')->id('remove_image_flag_tv')->value(0) }}
                        </div>
                    </div>
                    <div class="row gy-3">
                       <div class="col-md-4 col-lg-4 mb-3">
                        {{ html()->label(__('movie.lbl_name') . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                            {{ html()->text('name')->attribute('value', $data->name)->placeholder(__('placeholder.lbl_movie_name'))->class('form-control')->attribute('required','required') }}
                            <span class="text-danger" id="error_msg"></span>
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="name-error">Name field is required</div>
                       </div>

                        <div class="col-md-4 col-lg-4 mb-3">
                            {{ html()->label(__('movie.lbl_trailer_url_type').' <span class="text-danger">*</span>', 'type')->class('form-label') }}
                            {{ html()->select(
                                    'trailer_url_type',
                                    array_merge(
                                        $upload_url_type->pluck('name', 'value')->toArray(),
                                        ['Embedded' => 'Embedded']
                                    ),
                                    old('trailer_url_type', $data->trailer_url_type ?? '')
                                )->class('form-control select2')->id('trailer_url_type') }}
                            @error('trailer_url_type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="name-error">Trailer Type field is required</div>

                        </div>
                        <div class="col-md-4 col-lg-4 d-none" id="url_input">
                            {{ html()->label(__('movie.lbl_trailer_url').' <span class="text-danger">*</span>', 'trailer_url')->class('form-label') }}
                            {{ html()->text('trailer_url')->attribute('value', $data->trailer_url)->placeholder(__('placeholder.lbl_trailer_url'))->class('form-control') }}
                            @error('trailer_url')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="trailer-url-error">Video URL field is required</div>
                            <div class="invalid-feedback" id="trailer-pattern-error" style="display:none;">
                                Please enter a valid URL starting with http:// or https://.
                            </div>
                        </div>

                        <!-- Add the embed input section -->
                        <div class="col-md-4 col-lg-4 d-none" id="embed_input">
                            {{ html()->label(__('movie.lbl_embed_code'). ' <span class="text-danger">*</span>', 'trailer_embedded')->class('form-label') }}
                            {{ html()->textarea('trailer_embedded')
                                // ->attribute('value', old('trailer_embedded', $data->trailer_url_type === 'Embedded' ? $data->trailer_url : ''))
                                ->placeholder('<iframe ...></iframe>')
                                ->class('form-control')
                                ->id('trailer_embedded')
                                ->rows(4)
                                ->value(old('trailer_embedded', $data->trailer_url_type === 'Embedded' ? $data->trailer_url : '')) }}
                            @error('trailer_embedded')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="trailer-embed-error"></div>
                        </div>

                        <div class="col-md-4 col-lg-4 mt-3 position-relative d-none" id="url_file_input">
                            {{ html()->label(__('movie.lbl_trailer_video').' <span class="text-danger">*</span>', 'trailer_video')->class('form-label') }}

                            <div class="input-group btn-video-link-upload">
                                {{ html()->button(__(__('placeholder.lbl_select_file').'<i class="ph ph-upload"></i>'))
                                    ->class('input-group-text form-control')
                                    ->type('button')
                                    ->attribute('data-bs-toggle', 'modal')
                                    ->attribute('data-bs-target', '#exampleModal')
                                    ->attribute('data-image-container', 'selectedImageContainer3')
                                    ->attribute('data-hidden-input', 'file_url3')
                                }}

                                {{ html()->text('image_input3')
                                    ->class('form-control')
                                    ->placeholder(__('placeholder.lbl_select_file'))
                                    ->attribute('aria-label', 'Image Input 3')
                                    ->attribute('data-bs-toggle', 'modal')
                                    ->attribute('data-bs-target', '#exampleModal')
                                    ->attribute('data-image-container', 'selectedImageContainer3')
                                    ->attribute('data-hidden-input', 'file_url3')
                                }}
                            </div>
                            <div class="mt-3" id="selectedImageContainer3">
                                @if (Str::endsWith($data->trailer_url, ['.jpeg', '.jpg', '.png', '.gif']))
                                    <img class="img-fluid" src="{{ $data->trailer_url }}" style="width: 10rem; height: 10rem;">
                                @else
                                <video width="400" controls="controls" preload="metadata" >
                                    <source src="{{ $data->trailer_url }}" type="video/mp4" >
                                    </video>
                                @endif
                            </div>

                            {{ html()->hidden('trailer_video')->id('file_url3')->value($data->trailer_url)->attribute('data-validation', 'iq_video_quality') }}
                            @error('trailer_video')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="trailer-file-error">Video File field is required</div>

                        </div>
                    </div>
                    </div>

                    <div class="col-md-12">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            {{ html()->label(__('movie.lbl_description'). '<span class="text-danger"> *</span>', 'description')->class('form-label') }}
                            <span class="text-primary cursor-pointer" id="GenrateDescription" ><i class="ph ph-info" data-bs-toggle="tooltip" title="{{ __('messages.chatgpt_info') }}"></i> {{__('messages.lbl_chatgpt')}}</span>
                        </div>
                        {{ html()->textarea('description',$data->description)->class('form-control')->id('description')->placeholder(__('placeholder.lbl_movie_description'))->rows(4)->attribute('required','required') }}
                        @error('description')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="desc-error">Description field is required</div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        {{ html()->label(__('movie.lbl_movie_access') , 'movie_access')->class('form-label') }}
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-check form-check-inline form-control px-5 cursor-pointer" >
                            <div>
                                <input class="form-check-input" type="radio" name="movie_access" id="paid" value="paid"
                                    onchange="showPlanSelection(this.value === 'paid')"
                                    {{ $data->movie_access == 'paid' ? 'checked' : '' }} checked>
                                <span class="form-check-label" for="paid">{{__('movie.lbl_paid')}}</span>
                            </div>
                            </label>
                            <label class="form-check form-check-inline form-control px-5 cursor-pointer" >
                            <div>
                                <input class="form-check-input" type="radio" name="movie_access" id="free" value="free"
                                    onchange="showPlanSelection(this.value === 'paid')"
                                    {{ $data->movie_access == 'free' ? 'checked' : '' }}>
                                <span class="form-check-label" for="free">{{__('movie.lbl_free')}}</span>
                            </div>
                        </div>
                    </label>

                        {{-- <label class="form-check form-check-inline form-control px-5 cursor-pointer" >
                            <div>
                                <input class="form-check-input" type="radio" name="movie_access" id="pay-per-view" value="pay-per-view"
                                    onchange="showPlanSelection(this.value === 'paid')"
                                    {{ $data->movie_access == 'pay-per-view' ? 'checked' : '' }}>
                                <span class="form-check-label" for="free">{{__('messages.lbl_pay_per_view')}}</span>
                            </div>
                        </div>
                    </label> --}}
                        @error('movie_access')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12 row g-3 mt-2 {{ $data->movie_access == 'pay-per-view' ? '' : 'd-none' }}" id="payPerViewFields">

                        {{-- Price --}}
                        <div class="col-md-4">
                            {{ html()->label(__('messages.lbl_price') . '<span class="text-danger">*</span>', 'price')->class('form-label')->for('price') }}
                            {{ html()->number('price', old('price', $data->price))->class('form-control')->attribute('placeholder', __('messages.enter_price'))->required() }}
                            @error('price') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="invalid-feedback" id="price-error">Price field is required</div>

                        {{-- Purchase Type --}}
                        <div class="col-md-4">
                            {{ html()->label(__('messages.purchase_type'), 'purchase_type')->class('form-label') }}
                            {{ html()->select('purchase_type', [
                                    '' => __('messages.lbl_select_purchase_type'),
                                    'rental' => __('messages.lbl_rental'),
                                    'onetime' => __('messages.lbl_one_time_purchase')
                                ], old('purchase_type', $data->purchase_type ?? 'rental'))
                                ->id('purchase_type')
                                ->class('form-control select2')
                                ->attributes(['onchange' => 'toggleAccessDuration(this.value)'])
                            }}
                        </div>

                        {{-- Access Duration (Only for Rental) --}}
                        <div class="col-md-4 {{ $data->purchase_type == 'rental' ? '' : 'd-none' }}" id="accessDurationWrapper">
                            {{ html()->label(__('messages.lbl_access_duration') . __('messages.lbl_in_days'), 'access_duration')->class('form-label') }}
                            {{ html()->number('access_duration', old('access_duration', $data->access_duration))->class('form-control')->attribute('pattern', '[0-9]*')->attribute('oninput', 'this.value = this.value.replace(/[^0-9]/g, "")')->attribute('placeholder', __('messages.access_duration')) }}
                            @error('access_duration') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Discount --}}
                        <div class="col-md-4">
                            {{ html()->label(__('messages.lbl_discount') . ' (%)', 'discount')->class('form-label') }}
                            {{ html()->number('discount', old('discount', $data->discount))->class('form-control')->attribute('placeholder', __('messages.enter_discount'))->attribute('min', 1)->attribute('max', 99) }}
                            @error('discount') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-4">
                            {{ html()->label(__('messages.lbl_total_price'), 'total_amount')->class('form-label') }}
                            {{ html()->text('total_amount', null)->class('form-control')->attribute('disabled', true)->id('total_amount') }}
                        </div>
                        {{-- Available For --}}
                        <div class="col-md-4">
                            {{ html()->label(__('messages.lbl_available_for') . __('messages.lbl_in_days'), 'available_for')->class('form-label') }}
                            {{ html()->number('available_for', old('available_for', $data->available_for))->class('form-control')->attribute('pattern', '[0-9]*')->attribute('oninput', 'this.value = this.value.replace(/[^0-9]/g, "")')->attribute('placeholder', __('messages.available_for')) }}
                            @error('available_for') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                    </div>
                    <div class="col-md-6 col-lg-4 {{ old('movie_access', 'paid') == 'free' ? 'd-none' : '' }}" id="planSelection">
                        {{ html()->label(__('movie.lbl_select_plan'). '<span class="text-danger"> *</span>', 'type')->class('form-label') }}
                        {{ html()->select('plan_id', $plan->pluck('name', 'id')->prepend(__('placeholder.lbl_select_plan'), ''), $data->plan_id)->class('form-control select2')->id('plan_id') }}
                        @error('plan_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Plan field is required</div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        {{ html()->label(__('plan.lbl_status'), 'status')->class('form-label') }}
                        <div class="d-flex justify-content-between align-items-center form-control">
                            {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0 text-body') }}
                            <div class="form-check form-switch">
                                {{ html()->hidden('status', 0) }}
                                {{
                                    html()->checkbox('status', $data->status)
                                        ->class('form-check-input')
                                        ->id('status')
                                        ->value(1)
                                }}
                            </div>
                            @error('status')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>  

        <div class="d-flex align-items-center justify-content-between mt-5 pt-4 mb-3">
            <h5>{{ __('movie.lbl_basic_info') }}</h5>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="row gy-3">

                    <div class="col-md-6 col-lg-4">
                        {{ html()->label(__('movie.lbl_movie_language') . '<span class="text-danger">*</span>', 'language')->class('form-label') }}
                        {{ html()->select('language', $movie_language->pluck('name', 'value')->prepend(__('placeholder.lbl_select_language'), ''), $data->language)->class('form-control select2')->id('language')->attribute('required','required') }}
                        @error('language')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Language field is required</div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        {{ html()->label(__('movie.lbl_genres') . '<span class="text-danger">*</span>', 'genres')->class('form-label') }}
                        {{ html()->select('genres[]', $genres->pluck('name', 'id'),  $data->genres_data)->class('form-control select2')->id('genres')->multiple()->attribute('required','required') }}
                        @error('genres')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Genres field is required</div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        {{ html()->label(__('movie.lbl_countries'), 'countries')->class('form-label') }}
                        {{ html()->select('countries[]', $countries->pluck('name', 'id')->prepend(__('placeholder.lbl_select_country'), ''), old('countries', $data['countries'] ?? []))
                            ->class('form-control select2')
                            ->id('countries')
                            ->multiple() }}
                        @error('countries')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="countries-error">Countries field is required</div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        {{ html()->label(__('movie.lbl_imdb_rating') . ' <span class="text-danger">*</span>', 'IMDb_rating')->class('form-label') }}
                        {{ html()->text('IMDb_rating')
                                ->attribute('value', old('IMDb_rating', $data->IMDb_rating)) // Use old value or the existing movie value
                                ->placeholder(__('movie.lbl_imdb_rating'))
                                ->class('form-control')
                                ->required() }}

                        @error('IMDb_rating')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="imdb-error">IMDB Rating field is required</div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        {{ html()->label(__('movie.lbl_content_rating') . '<span class="text-danger">*</span>', 'content_rating')->class('form-label') }}

                        {{ html()->text('content_rating')->attribute('value', $data->content_rating)->placeholder(__('placeholder.lbl_content_rating'))->class('form-control')->attribute('required','required') }}

                        @error('content_rating')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Content Rating field is required</div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        {{ html()->label(__('movie.lbl_duration') . ' <span class="text-danger">*</span>', 'duration')->class('form-label') }}
                        {{ html()->time('duration')->attribute('value',  $data->duration)->placeholder(__('movie.lbl_duration'))->class('form-control min-datetimepicker-time')->attribute('required','required')->id('duration') }}
                        @error('duration')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="duration-error">Duration field is required</div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        {{ html()->label(__('movie.lbl_release_date').'<span class="text-danger">*</span>' , 'release_date')->class('form-label') }}
                        {{ html()->date('release_date')->attribute('value', $data->release_date)->placeholder(__('movie.lbl_release_date'))->class('form-control datetimepicker')->attribute('required','required')->id('release_date') }}
                        @error('release_date')
                            <span class="release_date-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Release Date field is required</div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        {{ html()->label(__('movie.lbl_age_restricted'), 'is_restricted')->class('form-label') }}
                        <div class="d-flex justify-content-between align-items-center form-control">
                            {{ html()->label(__('movie.lbl_restricted_content'), 'is_restricted')->class('form-label mb-0 text-body') }}
                            <div class="form-check form-switch">
                                {{ html()->hidden('is_restricted', 0) }}
                                {{ html()->checkbox('is_restricted', $data->is_restricted)->class('form-check-input')->id('is_restricted') }}
                            </div>
                        </div>
                        @error('is_restricted')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-5 pt-4 mb-3">
            <h5>{{ __('movie.lbl_actor_director') }}</h5>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        {{ html()->label(__('movie.lbl_actors') . '<span class="text-danger">*</span>', 'actors')->class('form-label') }}
                        {{ html()->select('actors[]', $actors->pluck('name', 'id'), $data->actors )->class('form-control select2')->id('actors')->multiple()->attribute('required','required') }}
                        @error('actors')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Actors field is required</div>
                    </div>
                    <div class="col-md-6">
                        {{ html()->label(__('movie.lbl_directors') . '<span class="text-danger">*</span>', 'directors')->class('form-label') }}
                        {{ html()->select('directors[]', $directors->pluck('name', 'id'), $data->directors )->class('form-control select2')->id('directors')->multiple()->attribute('required','required') }}
                        @error('directors')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Directors field is required</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">

            <button type="submit" class="btn btn-primary" id="submit-button">{{__('messages.save')}}</button>
        </div>
    </form>
    @include('components.media-modal')
@endsection
@push('after-scripts')
    <script>

document.addEventListener('DOMContentLoaded', function () {
        flatpickr('.min-datetimepicker-time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i", // Format for time (24-hour format)
            time_24hr: true // Enable 24-hour format

        });

        flatpickr('.datetimepicker', {
            dateFormat: "Y-m-d", // Format for date (e.g., 2024-08-21)

        });
    });


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
                var IframeField = document.querySelector('textarea[name="trailer_embedded"]');

                // Hide all inputs first
                FileInput.classList.add('d-none');
                URLInput.classList.add('d-none');
                EmbedInput.classList.add('d-none');

                // Remove all required attributes
                if (URLInputField) URLInputField.removeAttribute('required');
                if (trailerfile) trailerfile.removeAttribute('required');
                if (IframeField) IframeField.removeAttribute('required');

                if (selectedValue === 'Local') {
                    FileInput.classList.remove('d-none');
                    if (trailerfile) trailerfile.setAttribute('required', 'required');
                } else if (selectedValue === 'Embedded') {
                    EmbedInput.classList.remove('d-none');
                    if (IframeField) IframeField.setAttribute('required', 'required');
                } else if (selectedValue === 'URL' || selectedValue === 'YouTube' || selectedValue === 'HLS' || 
                           selectedValue === 'x265' || selectedValue === 'Vimeo') {
                    URLInput.classList.remove('d-none');
                    if (URLInputField) URLInputField.setAttribute('required', 'required');
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
            const selectedAccess = document.querySelector('input[name="movie_access"]:checked');
            const releaseDateField = document.querySelector('input[name="release_date"]').closest('.col-md-6');
            const releaseDateInput = document.querySelector('input[name="release_date"]');

            if (!selectedAccess) return;

            const value = selectedAccess.value;

            // Handle visibility and required attributes
            if (value === 'paid') {
                planSelection.classList.remove('d-none');
                payPerViewFields.classList.add('d-none');
                planIdSelect.setAttribute('required', 'required');
                priceInput.removeAttribute('required');
                releaseDateField.classList.remove('d-none');
                releaseDateInput.setAttribute('required', 'required');
            } else if (value === 'pay-per-view') {
                planSelection.classList.add('d-none');
                payPerViewFields.classList.remove('d-none');
                planIdSelect.removeAttribute('required');
                priceInput.setAttribute('required', 'required');
                releaseDateField.classList.add('d-none');
                releaseDateInput.removeAttribute('required');
            } else if (value === 'free') {
                planSelection.classList.add('d-none');
                payPerViewFields.classList.add('d-none');
                planIdSelect.removeAttribute('required');
                priceInput.removeAttribute('required');
                releaseDateField.classList.remove('d-none');
                releaseDateInput.setAttribute('required', 'required');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Initial setup
            showPlanSelection();

            // Event listeners for movie access radio buttons
            const accessRadios = document.querySelectorAll('input[name="movie_access"]');
            accessRadios.forEach(function (radio) {
                radio.addEventListener('change', showPlanSelection);
            });

            // Add form submit validation
            const form = document.getElementById('form-submit');
            form.addEventListener('submit', function(e) {
                const selectedAccess = document.querySelector('input[name="movie_access"]:checked');
                const releaseDateInput = document.querySelector('input[name="release_date"]');

                if (selectedAccess && selectedAccess.value !== 'pay-per-view' && !releaseDateInput.value) {
                    e.preventDefault();
                    releaseDateInput.classList.add('is-invalid');
                    const errorDiv = releaseDateInput.nextElementSibling;
                    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                        errorDiv.style.display = 'block';
                    }
                }
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


    function removeImage(hiddenInputId, removedFlagId) {
        var container = document.getElementById('selectedImageContainer2');
        var hiddenInput = document.getElementById(hiddenInputId);
        var removedFlag = document.getElementById(removedFlagId);

        container.innerHTML = '';
        hiddenInput.value = '';
        removedFlag.value = 1;
    }

    function removeThumbnail(hiddenInputId, removedFlagId) {
        var container = document.getElementById('selectedImageContainer1');
        var hiddenInput = document.getElementById(hiddenInputId);
        var removedFlag = document.getElementById(removedFlagId);

        container.innerHTML = '';
        hiddenInput.value = '';
        removedFlag.value = 1;
    }
    function removeTvImage(hiddenInputId, removedFlagId) {
        var container = document.getElementById('selectedImageContainertv');
        var hiddenInput = document.getElementById(hiddenInputId);
        var removedFlag = document.getElementById(removedFlagId);

        container.innerHTML = '';
        hiddenInput.value = '';
        removedFlag.value = 1;
    }

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


  $(document).ready(function() {

$('#GenrateDescription').on('click', function(e) {

    e.preventDefault();

    var description = $('#description').val();
    var name = $('#name').val();

    var generate_discription = "{{ route('backend.movies.generate-description') }}";
        generate_discription = generate_discription.replace('amp;', '');

    if (!description && !name) {
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
    const iframeMatch = value.match(/^<iframe[^>]+src="([^"]+)"[^>]*><\/iframe>$/i);
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
    const embedInput = document.getElementById('trailer_embedded');
    if (embedInput) {
        embedInput.addEventListener('input', () => validateEmbedInput('trailer_embedded', 'trailer-embed-error'));
    }

    // Form validation on button click
    const submitButton = document.getElementById('submit-button');
    if (submitButton) {
        submitButton.addEventListener('click', function(e) {
            const trailerType = document.getElementById('trailer_url_type')?.value;

            if (trailerType === 'Embedded') {
                if (!validateEmbedInput('trailer_embedded', 'trailer-embed-error')) {
                    e.preventDefault(); // Prevent form submission
                }
            }
        });
    }
});


    </script>

    <style>
        .position-relative {
            position: relative;
        }

        .position-absolute {
            position: absolute;
        }


    </style>
@endpush
