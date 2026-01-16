@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.parental_controls') }}
@endsection

@section('content')

    <div class="section-spacing">
        <div class="container-fluid">
            <div class="row gy-4">
                <div class="col-lg-3 col-md-4">
                    @include('frontend::components.account-settings-sidebar')
                </div>
                <div class="col-lg-9 col-md-8">
                    <div
                        class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4 {{ auth()->user()->pin ?: '' }}">
                        <div class="">
                            <h4 class="link-body-emphasis d-flex align-items-center gap-2">
                                {{ __('frontend.parental_controls') }}
                            </h4>
                            <p class="mb-0">{{ __('frontend.manage_content_restrictions_and_security_settings') }}</p>
                        </div>
                        <!-- Toggle Switch -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ph ph-lock-simple text-primary fs-5"></i>
                                <span class="fw-medium">{{ __('frontend.security_control') }}</span>
                            </div>
                            <label class="toggle-switch mb-0 ms-2">
                                <input type="checkbox" name="security_toggle" id="security_toggle" value="1"
                                    data-security-url="{{ route('security-control') }}"
                                    data-disable-url="{{ route('disable-security') }}"
                                    {{ (auth()->user()->is_parental_lock_enable && !empty(auth()->user()->pin)) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-5">
                        <div id="security_control_section"
                            class="{{ (auth()->user()->is_parental_lock_enable && !empty(auth()->user()->pin)) ? '' : 'd-none' }}">
                            <div class="tab-content">
                                @if (empty(auth()->user()->pin))
                                    <!---- change pin model -->
                                    <div class="tab-pane active fade show" id="changePin" role="tabpanel">
                                        <div class="edit-profile-content">
                                            <div class="edit-profile-details">
                                                <div class="card account-card">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-center gap-4 flex-wrap mb-5">
                                                            <div class="acc-icon bg-primary-subtle">
                                                                <i class="ph ph-shield"></i>
                                                            </div>
                                                            <div>
                                                                <h4 class="super-plan">{{ __('messages.pin_setting') }}</h4>
                                                                <p class="mb-0">{{ __('messages.pin_setup_description') }}</p>
                                                            </div>
                                                        </div>
                                                        <form id="editProfileDetail" autocomplete="off">
                                                            @csrf
                                                            <div>
                                                                <input type="hidden" name="type"
                                                                    class="form-control input-style-box" value="change_pin">
                                                            </div>
                                                            <div class="row gy-3">
                                                                <div class="col-md-6">
                                                                    <label
                                                                        class="form-label">{{ __('frontend.enter_pin') }}</label>
                                                                    <div id="otp-form"
                                                                        class="d-flex align-items-center gap-md-3 gap-2 otp-form">
                                                                        <input type="text" name="pin[]"
                                                                            class="otp-input" maxlength="1" required
                                                                            autofocus>
                                                                        <input type="text" name="pin[]"
                                                                            class="otp-input" maxlength="1" required>
                                                                        <input type="text" name="pin[]"
                                                                            class="otp-input" maxlength="1" required>
                                                                        <input type="text" name="pin[]"
                                                                            class="otp-input" maxlength="1" required>
                                                                    </div>
                                                                    <div class="invalid-feedback text-center"
                                                                        id="pin_error">{{ __('messages.pin_field_required') }}</div>
                                                                    <p class="text-danger text-center" id="pin_bk_error">
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label
                                                                        class="form-label">{{ __('frontend.confirm_pin') }}</label>
                                                                    <div id="otp-form"
                                                                        class="d-flex align-items-center gap-md-3 gap-2 otp-form">
                                                                        <input type="text" name="confirm_pin[]"
                                                                            class="otp-input" maxlength="1" required
                                                                            autofocus>
                                                                        <input type="text" name="confirm_pin[]"
                                                                            class="otp-input" maxlength="1" required>
                                                                        <input type="text" name="confirm_pin[]"
                                                                            class="otp-input" maxlength="1" required>
                                                                        <input type="text" name="confirm_pin[]"
                                                                            class="otp-input" maxlength="1" required>
                                                                    </div>
                                                                    <div class="invalid-feedback text-center"
                                                                        id="pin_error">{{ __('messages.confirm_pin_field_required') }}</div>
                                                                    <p class="text-danger text-center"
                                                                        id="confirm_pin_bk_error"></p>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-end mt-4">
                                                                <button type="button" id="updatePinBtn"
                                                                    class="btn btn-primary">{{ __('frontend.submit') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!---- end change pin model -->
                                @endif


                                @if (!empty(auth()->user()->pin))
                                    <!---- set parentral pin model -->
                                    <div class="tab-pane active fade show" id="changeParentPin" role="tabpanel">
                                        <div class="card user-login-card p-5">
                                            <div class="edit-profile-content">
                                                <div class="edit-profile-details">
                                                    <div class="bg-body rounded p-5 text-center">
                                                        <div
                                                            class="d-flex flex-md-nowrap flex-column justify-content-center gap-3">
                                                            <div>
                                                                @if (getCurrentProfileSession('is_child_profile') != 0)
                                                                    <h5 class="mb-3">{{ __('frontend.change_pins') }}
                                                                    </h5>
                                                                @endif
                                                                <p class="mb-0">{{ __('frontend.pin_change_notice') }}
                                                                </p>
                                                            </div>
                                                            <div class="flex-shrink-0">
                                                                <button id="sendOtpBtn"
                                                                    class="btn btn-primary">{{ __('frontend.send_otps') }}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!---- end set parentral pin model -->
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Otp verification Modal -->
    <div class="modal fade add-profile-modal" id="selectOTPModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content position-relative">
                <button type="button" class="btn btn-primary custom-close-btn rounded-2" data-bs-dismiss="modal">
                    <i class="ph ph-x text-white fw-bold align-middle"></i>
                </button>
                <form id="otpVerification" action="Post" class="requires-validation" data-toggle="validator"
                    novalidate>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            @csrf
                            <h5>{{ __('frontend.otp_verification_title') }}</h5>
                            <p class="mb-5">{{ __('frontend.otp_sent_message') }}</p>

                            <div id="otp-form" class="align-items-center gap-md-3 gap-2 otp-form mb-5">
                                <input type="text" id="otp1" name="otp[]" class="otp-input mr-2"
                                    maxlength="1" required>
                                <input type="text" name="otp[]" class="otp-input" maxlength="1" required>
                                <input type="text" name="otp[]" class="otp-input" maxlength="1" required>
                                <input type="text" name="otp[]" class="otp-input" maxlength="1" required>
                            </div>

                            <div class="invalid-feedback text-center" id="otp_error">
                                {{ __('frontend.otp_required_error') }}</div>
                            <p class="text-danger text-center" id="otp_bk_error"></p>
                        </div>

                        <div>
                            <span class="font-size-14">
                                {{ __('frontend.did_not_receive_otp') }}
                                <a href="javascript:void(0)" id="resendOtpBtn">{{ __('frontend.resend_otp') }}</a>
                            </span>
                        </div>

                        <div>
                            <button type="button" id="otpBtn"
                                class="btn btn-primary mt-5">{{ __('frontend.verify_otp') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pin Model Modal -->
    <div class="modal fade add-profile-modal" id="parentPinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content position-relative">
                <!-- Close button positioned at top-right -->
                <button type="button"
                    class="btn btn-primary custom-close-btn rounded-circle position-absolute top-0 end-0 m-2 p-2"
                    data-bs-dismiss="modal" aria-label="Close" style="width: 40px; height: 40px;">
                    <i class="ph ph-x text-white fw-bold align-middle fs-5"></i>
                </button>

                <div class="bg-body rounded p-5 text-center">
                    <h5 class="mb-3">{{ __('messages.set_new_parental_pin') }}</h5>
                    <form id="editProfileDetail">
                        @csrf
                        <div class="input-group mb-3">
                            <input type="hidden" name="type" class="form-control input-style-box"
                                value="change_pin">
                        </div>

                        <div class="mb-3">
                            <p class="text-center">{{ __('messages.enter_pin_label') }}</p>
                            <div id="otp-form" class="align-items-center gap-md-3 gap-2 otp-form mb-5">
                                <input type="text" name="pin[]" class="otp-input" maxlength="1" required>
                                <input type="text" name="pin[]" class="otp-input" maxlength="1" required>
                                <input type="text" name="pin[]" class="otp-input" maxlength="1" required>
                                <input type="text" name="pin[]" class="otp-input" maxlength="1" required>
                            </div>
                            <div class="invalid-feedback text-center" id="pin_error">{{ __('messages.pin_field_required') }}</div>
                            <p class="text-danger text-center" id="pin_bk_error"></p>
                        </div>

                        <div class="mb-3">
                            <p class="text-center">{{ __('messages.confirm_pin_label') }}</p>
                            <div id="otp-form" class="align-items-center gap-md-3 gap-2 otp-form mb-5">
                                <input type="text" name="confirm_pin[]" class="otp-input" maxlength="1" required>
                                <input type="text" name="confirm_pin[]" class="otp-input" maxlength="1" required>
                                <input type="text" name="confirm_pin[]" class="otp-input" maxlength="1" required>
                                <input type="text" name="confirm_pin[]" class="otp-input" maxlength="1" required>
                            </div>
                            <div class="invalid-feedback text-center" id="pin_error">{{ __('messages.confirm_pin_field_required') }}</div>
                            <p class="text-danger text-center" id="confirm_pin_bk_error"></p>
                        </div>

                        <div class="text-center">
                            <button type="button" id="updatePinBtn"
                                class="btn btn-primary mt-5">{{ __('frontend.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const pinMismatchMsg = "{{ __('messages.confirm_pin_not_match') }}";
        document.addEventListener('DOMContentLoaded', function() {
            $(document).ready(function() {
                $('#updatePinBtn').on('click', function(e) {
                    e.preventDefault();
                    $('.invalid-feedback').hide();
                    $('input').removeClass('is-invalid');
                    let valid = true;
                    const fieldsToValidate = [{
                            name: 'pin[]',
                            errorElement: '#pin_error'
                        },
                        {
                            name: 'confirm_pin[]',
                            errorElement: '#confirm_pin_error'
                        }
                    ];

                    fieldsToValidate.forEach(field => {
                        const value = $(`input[name="${field.name}"]`).val();
                        console.log(value);
                        if (!value) {
                            $(field.errorElement).show();
                            $(`input[name="${field.name}"]`).addClass('is-invalid');
                            valid = false;
                        }
                    });

                    if (!valid) {
                        return;
                    }

                    var pinVal = $('input[name="pin[]"]').map(function(){ return $(this).val(); }).get().join('');
                    var confirmPinVal = $('input[name="confirm_pin[]"]').map(function(){ return $(this).val(); }).get().join('');
                    if (pinVal !== confirmPinVal) {
                        $('#confirm_pin_bk_error').text(pinMismatchMsg);
                        return;
                    }

                    var formData = new FormData($('#editProfileDetail')[0]);

                    var $btn = $(this);
                    $btn.prop('disabled', true).text("{{ __('messages.updating') }}");

                    $.ajax({
                        url: `${baseUrl}/api/change-pin`,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'Authorization': 'Bearer ' + '{{ auth()->user()->api_token }}'
                        },
                        success: function(response) {

                            if (response.status === true) {
                                $('#pin_bk_error').html(null);
                                $('#confirm_pin_bk_error').html(null);
                                $("#editProfileDetail")[0].reset();
                                window.successSnackbar(response.message)
                                $btn.prop('disabled', false).text("{{ __('frontend.submit') }}");
                                $('#parentPinModal').modal('hide');
                                
                                // Turn ON the parental controls switch if PIN was just created
                                if (response.is_parental_lock_enable === 1 || response.is_parental_lock_enable === true) {
                                    const securityToggle = document.getElementById('security_toggle');
                                    const securityControlSection = document.getElementById('security_control_section');
                                    if (securityToggle) {
                                        securityToggle.checked = true;
                                    }
                                    if (securityControlSection) {
                                        securityControlSection.classList.remove('d-none');
                                    }
                                }
                                
                                // Hide the PIN setup form and show the "Get OTP" section without page refresh
                                if ($('#changePin').length) {
                                    $('#changePin').removeClass('active fade show').addClass('d-none');
                                }
                                
                                // Check if changeParentPin exists, if not create it dynamically
                                if ($('#changeParentPin').length) {
                                    $('#changeParentPin').removeClass('d-none').addClass('active fade show');
                                } else {
                                    // Dynamically create the "Get OTP" section
                                    var getOtpHtml = `
                                        <div class="tab-pane active fade show" id="changeParentPin" role="tabpanel">
                                            <div class="card user-login-card p-5">
                                                <div class="edit-profile-content">
                                                    <div class="edit-profile-details">
                                                        <div class="bg-body rounded p-5 text-center">
                                                            <div class="d-flex flex-md-nowrap flex-column justify-content-center gap-3">
                                                                <div>
                                                                    <p class="mb-0">{{ __('frontend.pin_change_notice') }}</p>
                                                                </div>
                                                                <div class="flex-shrink-0">
                                                                    <button id="sendOtpBtn" class="btn btn-primary">{{ __('frontend.send_otps') }}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                    $('.tab-content').append(getOtpHtml);
                                    
                                    // Re-bind the sendOtpBtn handler for the dynamically created element
                                    $('#sendOtpBtn').off('click').on('click', function(e) {
                                        var $btn = $(this);
                                        $btn.prop('disabled', true).text(window.translations.sending);

                                        $.ajax({
                                            url: `${baseUrl}/api/send-otp`,
                                            type: 'GET',
                                            success: function(response) {
                                                if (response.status === true) {
                                                    window.successSnackbar(window.translations.otp_send_success);
                                                    $('#selectOTPModal').modal('show');
                                                } else {
                                                    window.successSnackbar(window.translations.otp_send_error);
                                                }
                                                $btn.prop('disabled', false).text(window.translations.send_otp);
                                            },
                                            error: function(xhr, status, error) {
                                                let response = JSON.parse(xhr.responseText);
                                                window.successSnackbar(response.message || window.translations.otp_send_error);
                                                $btn.prop('disabled', false).text(window.translations.send_otp);
                                            }
                                        });
                                    });
                                }

                            } else {
                                window.successSnackbar(response.message)
                                $btn.prop('disabled', false).text("{{ __('frontend.submit') }}");
                            }
                        },
                        error: function(xhr, status, error) {
                            var response = JSON.parse(xhr.responseText);

                            if (response.errors && response.errors.pin) {
                                $('#pin_bk_error').html(response.errors.pin[0]);
                            }

                            if (response.errors && response.errors.confirm_pin) {
                                $('#confirm_pin_bk_error').html(response.errors
                                    .confirm_pin[0]);
                            }

                            $btn.prop('disabled', false).text("{{ __('frontend.submit') }}");
                        }
                    });
                });


                $('#sendOtpBtn').on('click', function(e) {
                    var $btn = $(this);
                    $btn.prop('disabled', true).text(window.translations.sending);

                    $.ajax({
                        url: `${baseUrl}/api/send-otp`,
                        type: 'GET',
                        success: function(response) {
                            if (response.status === true) {
                                window.successSnackbar(window.translations
                                    .otp_send_success);
                                $('#selectOTPModal').modal('show');
                            } else {
                                window.successSnackbar(window.translations
                                    .otp_send_error);
                            }

                            $btn.prop('disabled', false).text(window.translations
                                .send_otp);
                        },
                        error: function(xhr, status, error) {
                            let response = JSON.parse(xhr.responseText);
                            window.successSnackbar(response.message || window
                                .translations.otp_send_error);
                            $btn.prop('disabled', false).text(window.translations
                                .send_otp);
                        }
                    });
                });





                $('#otpBtn').on('click', function(e) {
                    e.preventDefault();
                    $('.invalid-feedback').hide();
                    $('input').removeClass('is-invalid');
                    let valid = true;
                    const fieldsToValidate = [{
                        name: 'otp[]',
                        errorElement: '#pin_error'
                    }];

                    fieldsToValidate.forEach(field => {
                        const value = $(`input[name="${field.name}"]`).val();
                        console.log(value);
                        if (!value) {
                            $(field.errorElement).show();
                            $(`input[name="${field.name}"]`).addClass('is-invalid');
                            valid = false;
                        }
                    });

                    if (!valid) {
                        return;
                    }

                    var formData = new FormData($('#otpVerification')[0]);

                    var $btn = $(this);
                    $btn.prop('disabled', true).text("{{ __('messages.verifying') }}");

                    $.ajax({
                        url: `${baseUrl}/api/verify-otp`,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'Authorization': 'Bearer ' + '{{ auth()->user()->api_token }}'
                        },
                        success: function(response) {
                            if (response.status === true) {
                                $('#otp_bk_error').html(null);
                                $("#otpVerification")[0].reset();
                                window.successSnackbar(response.message)
                                $btn.prop('disabled', false).text("{{ __('messages.verify_otp') }}");
                                $('#selectOTPModal').modal('hide');
                                $('#parentPinModal').modal('show');

                            } else {
                                window.successSnackbar("{{ __('messages.invalid_otp_message') }}")
                                $btn.prop('disabled', false).text("{{ __('messages.verify_otp') }}");
                            }
                        },
                        error: function(xhr, status, error) {
                            var response = JSON.parse(xhr.responseText);

                            if (response.errors && response.errors.otp) {
                                $('#otp_bk_error').html(response.errors.otp[0]);
                            }
                            $('#otp_bk_error').html(response.message);
                            $btn.prop('disabled', false).text("{{ __('messages.verify_otp') }}");
                        }
                    });
                });


                $('#resendOtpBtn').on('click', function(e) {
                    var $btn = $(this);
                    $btn.prop('disabled', true).text("{{ __('messages.sending') }}");
                    $.ajax({
                        url: `${baseUrl}/api/send-otp`,
                        type: 'GET',
                        success: function(response) {
                            if (response.status === true) {
                                window.successSnackbar(response.message)
                                $btn.prop('disabled', false).text("{{ __('frontend.resend_otp') }}");

                            } else {
                                window.successSnackbar("{{ __('messages.error_change_pin') }}")
                                $btn.prop('disabled', false).text("{{ __('frontend.resend_otp') }}");
                            }
                        },
                        error: function(xhr, status, error) {
                            var response = JSON.parse(xhr.responseText);
                            window.successSnackbar(response.message);
                            $btn.prop('disabled', false).text("{{ __('frontend.resend_otp') }}");
                        }

                    });
                });

            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            const otpInputs = document.querySelectorAll(".otp-input");

            otpInputs.forEach((input, index) => {
                input.addEventListener("input", function() {
                    this.value = this.value.replace(/[^0-9]/g, ''); // Allow only digits
                    if (this.value.length === 1) {
                        const next = otpInputs[index + 1];
                        if (next) next.focus();
                    }
                });

                input.addEventListener("keydown", function(e) {
                    if (e.key === "Backspace" && !this.value) {
                        const prev = otpInputs[index - 1];
                        if (prev) prev.focus();
                    }
                });
            });
        });


        document.addEventListener('DOMContentLoaded', function() {
            var selectOTPModal = document.getElementById('selectOTPModal');

            // Function to initialize OTP input behavior
            function initializeOtpInputs() {
                const otpInputs = document.querySelectorAll('#otp-form .otp-input');

                otpInputs.forEach((input, index) => {
                    input.addEventListener('input', function() {
                        this.value = this.value.replace(/[^0-9]/g, ''); // Allow only digits
                        if (this.value.length === 1) {
                            const next = otpInputs[index + 1];
                            if (next) next.focus();
                        }
                    });

                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Backspace' && !this.value) {
                            const prev = otpInputs[index - 1];
                            if (prev) prev.focus();
                        }
                    });
                });
            }

            // Set focus when modal is shown
            selectOTPModal.addEventListener('shown.bs.modal', function() {
                const firstOtpInput = document.getElementById('otp1');
                if (firstOtpInput) {
                    firstOtpInput.focus();
                }
                initializeOtpInputs();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const pinInputs = document.querySelectorAll('.otp-input');
            const parentPinModal = document.getElementById('parentPinModal');

            // Initialize PIN input behavior
            function initializePinInputs() {
                pinInputs.forEach((input, index) => {
                    input.addEventListener('input', function() {
                        this.value = this.value.replace(/[^0-9]/g, ''); // Allow only digits
                        if (this.value.length === 1 && index < pinInputs.length - 1) {
                            pinInputs[index + 1].focus();
                        }
                    });

                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Backspace' && !this.value && index > 0) {
                            pinInputs[index - 1].focus();
                        }
                    });
                });
            }

            // Set focus when modal is shown
            parentPinModal.addEventListener('shown.bs.modal', function() {
                pinInputs[0].focus();
                initializePinInputs();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('security_toggle');
            const securityControlSection = document.getElementById('security_control_section');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }

            if (toggle) {
                const securityUrl = toggle.dataset.securityUrl;
                const disableUrl = toggle.dataset.disableUrl;

                toggle.addEventListener('change', function() {
                    toggle.disabled = true;

                    if (this.checked) {
                        fetch(securityUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                credentials: 'same-origin'
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.status === 'success') {
                                    toggle.checked = true;
                                    if (securityControlSection) {
                                        securityControlSection.classList.remove('d-none');
                                    }
                                } else {
                                    throw new Error('Failed to enable security');
                                }
                            })
                            .catch(error => {
                                console.error('Error details:', error);
                                toggle.checked = false;
                                if (securityControlSection) {
                                    securityControlSection.classList.add('d-none');
                                }
                                if (window.successSnackbar) {
                                    window.successSnackbar('{{ __('frontend.error_enabling_security') }}');
                                }
                            })
                            .finally(() => {
                                toggle.disabled = false;
                            });

                    } else {
                        fetch(disableUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                credentials: 'same-origin'
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.status === 'success') {
                                    toggle.checked = false;
                                    if (securityControlSection) {
                                        securityControlSection.classList.add('d-none');
                                    }
                                } else {
                                    throw new Error('Failed to disable security');
                                }
                            })
                            .catch(error => {
                                console.error('Error details:', error);
                                toggle.checked = true;
                                if (securityControlSection) {
                                    securityControlSection.classList.remove('d-none');
                                }
                                if (window.successSnackbar) {
                                    window.successSnackbar('{{ __('frontend.error_disabling_security') }}');
                                }
                            })
                            .finally(() => {
                                toggle.disabled = false;
                            });
                    }
                });
            }
        });
    </script>

@endsection
