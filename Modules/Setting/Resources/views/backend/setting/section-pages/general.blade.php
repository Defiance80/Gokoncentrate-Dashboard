@extends('setting::backend.setting.index')
@section('title')
    {{ __('setting_sidebar.lbl_General') }}
@endsection

@section('settings-content')
    {{ html()->form('POST', route('backend.setting.store'))->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->class('requires-validation')->attribute('novalidate', 'novalidate')->attribute('enctype', 'multipart/form-data')->open() }}
    @csrf
    <input type="hidden" name="setting_tab" value="business">
    <div class="d-flex justify-content-between align-items-center mb-3">

        <h3 class="mb-0"> <i class="fas fa-cube"></i> {{ __('setting_sidebar.lbl_General') }}</h3>


        <div>
            <button type="button" class="btn btn-primary float-right" onclick="clearCache()">
                <i class="fa-solid fa-arrow-rotate-left mx-2"></i>{{ __('settings.purge_cache') }}
            </button>


        </div>
    </div>


    <div class="form-group">
        <label class="form-label">{{ __('setting_bussiness_page.lbl_app') }} <span class="text-danger">*</span></label>
        {{ html()->text('app_name')->class('form-control')->value($data['app_name'] ?? old('app_name'))->required()->placeholder(__('setting_bussiness_page.placeholder_app_name')) }}
        <div class="invalid-feedback" id="name-error">App field is required</div>
    </div>


    <div class="form-group">
        <label class="form-label">{{ __('setting_bussiness_page.lbl_contact_no') }} <span
                class="text-danger">*</span></label>
        {{ html()->text('helpline_number')->class('form-control')->value($data['helpline_number'] ?? old('helpline_number'))->required()->placeholder(__('setting_bussiness_page.placeholder_contact_no')) }}
        <div class="invalid-feedback" id="name-error">Contact No. field is required</div>
    </div>

    <div class="form-group">
        <label class="form-label">{{ __('setting_bussiness_page.lbl_inquiry_email') }} <span
                class="text-danger">*</span></label>
        {{ html()->email('inquriy_email')->class('form-control')->value($data['inquriy_email'] ?? old('inquriy_email'))->required()->placeholder(__('setting_bussiness_page.placeholder_inquiry_email')) }}
        <div class="invalid-feedback" id="name-error">Inquiry email field is required</div>
    </div>

    <div class="form-group">
        <label class="form-label">{{ __('setting_bussiness_page.lbl_site_description') }} <span
                class="text-danger">*</span></label>
        {{ html()->text('short_description')->class('form-control')->value($data['short_description'] ?? old('short_description'))->required()->placeholder(__('setting_bussiness_page.placeholder_short_description')) }}
        <div class="invalid-feedback" id="name-error">Short description field is required</div>
    </div>

    <div class="form-group">
        <label class="form-label">{{ __('setting_bussiness_page.lbl_copyright_text') }} <span class="text-danger">*</span></label>
        {{ html()->text('copyright_text')->class('form-control')->value($data['copyright_text'] ?? old('copyright_text'))->required()->placeholder('© 2025 GoKoncentrate. All Rights Reserved.') }}
        @error('copyright_text')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @else
            <div class="invalid-feedback" id="copyright-text-error">Copyright text field is required</div>
        @enderror
    </div>

    <!-- Social Links -->
    <div class="row">
        <div class="form-group col-md-6">
            <label class="form-label">{{ __('setting_bussiness_page.facebook_url') }} <span
                    class="text-danger">*</span></label>
            {{ html()->text('facebook_url')->class('form-control' . ($errors->has('facebook_url') ? ' is-invalid' : ''))->value($data['facebook_url'] ?? old('facebook_url'))->required()->placeholder(__('setting_bussiness_page.placeholder_facebook_url')) }}
            @error('facebook_url')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="invalid-feedback">Facebook URL field is required and must be a valid URL</div>
            @enderror
        </div>
        <div class="form-group col-md-6">
            <label class="form-label">{{ __('setting_bussiness_page.x_url') }} <span class="text-danger">*</span></label>
            {{ html()->text('x_url')->class('form-control' . ($errors->has('x_url') ? ' is-invalid' : ''))->value($data['x_url'] ?? old('x_url'))->required()->placeholder(__('setting_bussiness_page.placeholder_x_url')) }}
            @error('x_url')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="invalid-feedback">X (Twitter) URL field is required and must be a valid URL</div>
            @enderror
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-6">
            <label class="form-label">{{ __('setting_bussiness_page.instagram_url') }} <span
                    class="text-danger">*</span></label>
            {{ html()->text('instagram_url')->class('form-control' . ($errors->has('instagram_url') ? ' is-invalid' : ''))->value($data['instagram_url'] ?? old('instagram_url'))->required()->placeholder(__('setting_bussiness_page.placeholder_instagram_url')) }}
            @error('instagram_url')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="invalid-feedback">Instagram URL field is required and must be a valid URL</div>
            @enderror
        </div>
        <div class="form-group col-md-6">
            <label class="form-label">{{ __('setting_bussiness_page.youtube_url') }} <span
                    class="text-danger">*</span></label>
            {{ html()->text('youtube_url')->class('form-control' . ($errors->has('youtube_url') ? ' is-invalid' : ''))->value($data['youtube_url'] ?? old('youtube_url'))->required()->placeholder(__('setting_bussiness_page.placeholder_youtube_url')) }}
            @error('youtube_url')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="invalid-feedback">YouTube URL field is required and must be a valid URL</div>
            @enderror
        </div>
    </div>
    <div class="row ">


        <!-- Mini Logo Upload -->
        <div class="form-group mb-3 col-md-6">
            <label for="mini_logo" class="form-label">{{ __('messages.mini_logo') }}</label>
            <div class="row align-items-center">
                <div class="col-lg-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <img id="miniLogoViewer" src="{{ isset($data['mini_logo']) && $data['mini_logo']!= null ? $data['mini_logo'] : asset('img/logo/mini_logo.png') }}"
                                class="img-fluid" alt="mini_logo" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2">
                        <input type="hidden" id="mini_logo" name="mini_logo" value="{{ isset($data['mini_logo']) && $data['mini_logo']!= null ? $data['mini_logo'] : '' }}">
                        <button type="button" class="btn btn-primary mb-5" data-bs-toggle="modal" data-bs-target="#exampleModal" data-image-container="miniLogoViewer" data-hidden-input="mini_logo" data-page-type="logos">
                            {{ __('messages.upload') }}
                        </button>
                        <button type="button" class="btn btn-dark mb-5"
                            id="removeMiniLogoButton">{{ __('messages.remove') }}</button>
                    </div>
                    <span class="text-danger" id="error_mini_logo"></span>
                </div>
            </div>
        </div>

        <!-- Dark Logo Upload -->
        <div class="form-group mb-3 col-md-6">
            <label for="dark_logo" class="form-label">{{ __('messages.dark_logo') }}</label>
            <div class="row align-items-center">
                <div class="col-lg-4">
                    <div class="card text-center bg-dark">
                        <div class="card-body">
                            <img id="darkLogoViewer" src="{{ isset($data['dark_logo']) && $data['dark_logo'] != null ? $data['dark_logo'] : asset('img/logo/dark_logo.png') }}"
                                class="img-fluid" alt="dark_logo" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2">
                        <input type="hidden" id="dark_logo" name="dark_logo" value="{{ isset($data['dark_logo']) && $data['dark_logo'] != null ? $data['dark_logo'] : '' }}">
                        <button type="button" class="btn btn-primary mb-5" data-bs-toggle="modal" data-bs-target="#exampleModal" data-image-container="darkLogoViewer" data-hidden-input="dark_logo" data-page-type="logos">
                            {{ __('messages.upload') }}
                        </button>
                        <button type="button" class="btn btn-dark mb-5"
                            id="removeDarkLogoButton">{{ __('messages.remove') }}</button>
                    </div>
                    <span class="text-danger" id="error_dark_logo"></span>
                </div>
            </div>
        </div>



        <!-- Favicon -->
        <div class="form-group mb-3 col-md-6">
            <label for="favicon" class="form-label">{{ __('messages.favicon') }}</label>
            <div class="row align-items-center">
                <div class="col-lg-4">
                    <div class="card text-center bg-light">
                        <div class="card-body">
                            <img id="faviconViewer" src="{{ isset($data['favicon']) && $data['favicon'] != null ? $data['favicon'] : asset('img/logo/favicon.png') }}"
                                class="img-fluid" alt="favicon_logo" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2">
                        <input type="hidden" id="favicon" name="favicon" value="{{ isset($data['favicon']) && $data['favicon'] != null ? $data['favicon'] : '' }}">
                        <input type="hidden" id="favicon_remove" name="favicon_remove" value="0">
                        <button type="button" class="btn btn-primary mb-5" data-bs-toggle="modal" data-bs-target="#exampleModal" data-image-container="faviconViewer" data-hidden-input="favicon" data-page-type="logos">
                            {{ __('messages.upload') }}
                        </button>
                        <button type="button" class="btn btn-dark mb-5"
                            id="removeFaviconButton">{{ __('messages.remove') }}</button>
                    </div>
                    <span class="text-danger" id="error_favicon"></span>
                </div>
            </div>
        </div>

        <div class="form-group mb-3 col-md-6">
            <label class="form-label">{{ __('messages.loader_gif') }}</label>
            <div class="row align-items-center">
                <div class="col-lg-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <img id="loaderGifViewer" src="{{ isset($data['loader_gif']) && $data['loader_gif'] != null ? $data['loader_gif'] : asset('img/logo/loader.gif') }}"
                                class="img-fluid" alt="loader_logo" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2">
                        <input type="hidden" id="loader_gif" name="loader_gif" value="{{ isset($data['loader_gif']) && $data['loader_gif'] != null ? $data['loader_gif'] : '' }}">
                        <input type="hidden" id="loader_gif_remove" name="loader_gif_remove" value="0">
                        <button type="button" class="btn btn-primary mb-5" data-bs-toggle="modal" data-bs-target="#exampleModal" data-image-container="loaderGifViewer" data-hidden-input="loader_gif" data-page-type="logos">
                            {{ __('messages.upload') }}
                        </button>
                        <button type="button" class="btn btn-dark mb-5"
                            id="removeLoaderGifButton">{{ __('messages.remove') }}</button>
                    </div>
                    <span class="text-danger" id="error_loader_gif"></span>
                </div>
            </div>
        </div>

        <div class="text-end">
            {{ html()->button(__('messages.save'))->type('submit')->attribute('id', 'submit-button')->class('btn btn-primary')->id('submit-button') }}
        </div>
    </div>
    {{ html()->form()->close() }}
        @include('components.media-modal', ['page_type' => 'logos'])
    @endsection
    @push('after-scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Update image previews when modal closes
                const exampleModal = document.getElementById('exampleModal');
                if (exampleModal) {
                    exampleModal.addEventListener('hidden.bs.modal', function() {
                        const miniLogo = document.getElementById('mini_logo');
                        const darkLogo = document.getElementById('dark_logo');
                        const favicon = document.getElementById('favicon');
                        const loaderGif = document.getElementById('loader_gif');
                        
                        if (miniLogo && miniLogo.value) document.getElementById('miniLogoViewer').src = miniLogo.value;
                        if (darkLogo && darkLogo.value) document.getElementById('darkLogoViewer').src = darkLogo.value;
                        if (favicon && favicon.value) {
                            document.getElementById('faviconViewer').src = favicon.value;
                            const faviconRemove = document.getElementById('favicon_remove');
                            if (faviconRemove) faviconRemove.value = '0';
                        }
                        if (loaderGif && loaderGif.value) {
                            document.getElementById('loaderGifViewer').src = loaderGif.value;
                            const loaderGifRemove = document.getElementById('loader_gif_remove');
                            if (loaderGifRemove) loaderGifRemove.value = '0';
                        }
                    });
                }

                document.getElementById('removeMiniLogoButton').addEventListener('click', function() {
                    const miniLogoViewer = document.getElementById('miniLogoViewer');
                    const defaultMiniLogo = "{{ asset('img/logo/mini_logo.png') }}";

                    // Reset the mini logo image to default
                    miniLogoViewer.src = defaultMiniLogo;

                    // Clear the file input
                    const miniLogoInput = document.getElementById('mini_logo');
                    miniLogoInput.value = '';

                    // Clear any validation errors
                    document.getElementById('error_mini_logo').innerText = '';
                });

                document.getElementById('removeDarkLogoButton').addEventListener('click', function() {
                    const darkLogoViewer = document.getElementById('darkLogoViewer');
                    const defaultDarkLogo = "{{ asset('img/logo/dark_logo.png') }}";

                    // Reset the dark logo image to the default
                    darkLogoViewer.src = defaultDarkLogo;

                    // Clear the file input
                    const darkLogoInput = document.getElementById('dark_logo');
                    darkLogoInput.value = '';

                    // Clear any validation errors
                    document.getElementById('error_dark_logo').innerText = '';
                });


                document.getElementById('removeFaviconButton').addEventListener('click', function() {
                    const faviconViewer = document.getElementById('faviconViewer');
                    const defaultFavicon = "{{ asset('img/logo/favicon.png') }}";

                    // Reset favicon image to default
                    faviconViewer.src = defaultFavicon;

                    // Clear the file input
                    const faviconInput = document.getElementById('favicon');
                    faviconInput.value = '';
                    const faviconRemoveInput = document.getElementById('favicon_remove');
                    faviconRemoveInput.value = '1';

                    // Clear any error messages
                    document.getElementById('error_favicon').innerText = '';
                });
            });

            document.getElementById('removeLoaderGifButton').addEventListener('click', function() {
                const loaderGifViewer = document.getElementById('loaderGifViewer');
                const defaultLoaderGif = "{{ asset('img/logo/loader.gif') }}";

                loaderGifViewer.src = defaultLoaderGif;

                const loaderGifInput = document.getElementById('loader_gif');
                loaderGifInput.value = '';

                const loaderGifRemoveInput = document.getElementById('loader_gif_remove');
                loaderGifRemoveInput.value = '1';
                document.getElementById('error_loader_gif').innerText = '';
            });

            function clearCache() {
                Swal.fire({
                    title: '{{ __('messages.are_you_sure') }}',
                    text: "{{ __('messages.are_you_sure_you_want_to_clear_the_cache') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '{{ __('messages.yes_clear_it') }}',
                    cancelButtonText: '{{ __('messages.cancel') }}',
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('{{ route('backend.settings.clear-cache') }}', {
                                method: 'GET',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status) {
                                    Swal.fire({
                                        title: '{{ __('messages.success') }}',
                                        text: '{{ __('messages.cache_clear_successfully') }}', // Use the dynamic message from the server
                                        icon: 'success',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'An unexpected error occurred.',
                                        icon: 'error',
                                        showConfirmButton: true
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error clearing cache:', error);
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An error occurred while clearing the cache.',
                                    icon: 'error',
                                    showConfirmButton: true
                                });
                            });
                    }
                });
            }


            function resetDatabase() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Are you sure you want to reset the Database?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, reset it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Change button text to "Loading..." and disable it
                        let button = document.querySelector('button[onclick="resetDatabase()"]');
                        button.disabled = true;
                        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mx-2"></i>Loading...';

                        fetch('{{ route('backend.settings.database-reset') }}', {
                                method: 'GET',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status) {
                                    Swal.fire({
                                        title: '{{ __('messages.success') }}',
                                        text: 'Database reset successfully', // Use the dynamic message from the server
                                        icon: 'success',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'An unexpected error occurred.',
                                        icon: 'error',
                                        showConfirmButton: true
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error clearing cache:', error);
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An error occurred while resetting the database.',
                                    icon: 'error',
                                    showConfirmButton: true
                                });
                            })
                            .finally(() => {
                                // Reset the button text and enable it after the request
                                button.disabled = false;
                                button.innerHTML =
                                    '<i class="fa-solid fa-arrow-rotate-left mx-2"></i>{{ __('setting_sidebar.lbl_database_reset') }}';
                            });
                    }
                });
            }

            const minilogoInput = document.getElementById('mini_logo');
            const miniLogoViewer = document.getElementById('miniLogoViewer');

            minilogoInput.addEventListener('change', function() {
                const minilogofile = this.files[0];
                if (minilogofile) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        miniLogoViewer.src = e.target.result;
                    }
                    reader.readAsDataURL(minilogofile);
                }
            });

            const darklogoInput = document.getElementById('dark_logo');
            const darkLogoViewer = document.getElementById('darkLogoViewer');

            darklogoInput.addEventListener('change', function() {
                const darklogofile = this.files[0];
                if (darklogofile) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        darkLogoViewer.src = e.target.result;
                    }
                    reader.readAsDataURL(darklogofile);
                }
            });



            const faviconInput = document.getElementById('favicon');
            const faviconViewer = document.getElementById('faviconViewer');

            faviconInput.addEventListener('change', function() {
                const faviconFile = this.files[0];
                if (faviconFile) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        faviconViewer.src = e.target.result;
                    }
                    reader.readAsDataURL(faviconFile);
                }
            });

            const loaderGifInput = document.getElementById('loader_gif');
            const loaderGifViewer = document.getElementById('loaderGifViewer');

            loaderGifInput.addEventListener('change', function() {
                const loaderGifFile = this.files[0];
                if (loaderGifFile) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        loaderGifViewer.src = e.target.result;
                    }
                    reader.readAsDataURL(loaderGifFile);
                }
            });
        </script>
    @endpush
