@extends('layouts.auth.app')

@section('title')
    {{ __('Register') }}
@endsection

@section('main_content')
    <div class="footer position-relative">
        <div class="mybazar-login-section ">
            <div class="mybazar-login-wrapper ">
                <div class="login-wrapper">
                    <div class="login-body w-100">
                        <div class="footer-logo w-100  ">
                            @php
                                $logoUrl = asset(get_option('general')['login_page_logo'] ?? 'assets/images/icons/logo.svg');
                            @endphp
                            <div class="logo-wrapper" style="width: 340px; max-width: 100%; height: 84px; margin: 0 auto;">
                                <div class="theme-logo-mask"
                                    style="-webkit-mask-image: url('{{ $logoUrl }}'); mask-image: url('{{ $logoUrl }}');">
                                </div>
                            </div>
                        </div>
                        <h2 class="login-title">{{ __('Create Account') }}</h2>
                        <h6 class="login-para">{{ __('Please fill in your details to register') }}</h6>

                        @if ($errors->any())
                            <div class="alert alert-danger mt-3">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('custom.register.start') }}" id="registerForm" novalidate>
                            @csrf
                            <style>
                                ::placeholder {
                                    color: #9ca3af !important;
                                    opacity: 1;
                                    /* Firefox */
                                }

                                :-ms-input-placeholder {
                                    /* Internet Explorer 10-11 */
                                    color: #9ca3af !important;
                                }

                                ::-ms-input-placeholder {
                                    /* Microsoft Edge */
                                    color: #9ca3af !important;
                                }

                                .input-group {
                                    margin-top: 2px !important;
                                }

                                /* Modal Refinements */
                                .modal-header {
                                    background-color:
                                        {{ config('services.theme.color') }}
                                        !important;
                                    color: #ffffff !important;
                                    border-bottom: none !important;
                                }

                                .modal-header .modal-title,
                                .modal-header .modal-title span {
                                    color: #ffffff !important;
                                }

                                .modal-header .btn-close {
                                    filter: brightness(0) invert(1);
                                    opacity: 0.8;
                                }

                                .modal-header .btn-close:hover {
                                    opacity: 1;
                                }

                                .modal-header i,
                                .modal-header i.fa-solid {
                                    color: #ffffff !important;
                                }

                                /* Modal Footer Button Refinements */
                                .modal-footer .btn {
                                    padding: 8px 24px !important;
                                    border-radius: 8px !important;
                                    font-weight: 500 !important;
                                    display: inline-flex !important;
                                    align-items: center !important;
                                    justify-content: center !important;
                                    transition: all 0.2s ease !important;
                                }



                                .modal-footer .btn-primary {
                                    background-color:
                                        {{ config('services.theme.color') }}
                                        !important;
                                    border-color:
                                        {{ config('services.theme.color') }}
                                        !important;
                                    color: #fff !important;
                                }

                                .modal-footer .btn-primary:hover {
                                    background-color:
                                        {{ config('services.theme.color') }}
                                        !important;
                                    border-color:
                                        {{ config('services.theme.color') }}
                                        !important;
                                    opacity: 0.9;
                                    box-shadow: 0 4px 12px
                                        {{ config('services.theme.color') }}
                                        4d !important;
                                }

                                .modal-footer .btn-outline-danger {
                                    border-color: #ff4d4d !important;
                                    color: #ff4d4d !important;
                                    background: transparent !important;
                                }

                                .modal-footer .btn-outline-danger:hover {
                                    background-color: #ff4d4d !important;
                                    color: #fff !important;
                                    box-shadow: 0 4px 12px rgba(255, 77, 77, 0.2) !important;
                                }

                                .btn:focus,
                                .btn:active:focus,
                                .btn-primary:focus,
                                .btn-secondary:focus,
                                .btn-outline-danger:focus,
                                .btn-close:focus,
                                .btn:focus-visible,
                                .btn-primary:focus-visible,
                                .btn-secondary:focus-visible,
                                .btn-outline-danger:focus-visible,
                                .btn-close:focus-visible {
                                    outline: none !important;
                                    box-shadow: none !important;
                                }
                            </style>
                            <div class="mb-1">
                                <label for="nameInput" class="form-label mb-0"
                                    style="font-size: 13px; font-weight: 500;">{{ __("User Name") }}</label>
                                <div class="input-group">
                                    <span class="input-icon"><img src="{{ asset('assets/images/icons/user-dark.svg') }}"
                                            alt="img"></span>
                                    <input type="text" name="name" id="nameInput" class="form-control w-100 dynamictext"
                                        value="{{ old('name') }}" placeholder="{{ __('Enter your Username') }}" autofocus>
                                </div>
                            </div>

                            <div class="mb-1">
                                <label for="emailInput" class="form-label mb-0"
                                    style="font-size: 13px; font-weight: 500;">{{ __("Email Address") }}</label>
                                <div class="input-group">
                                    <span class="input-icon"><img src="{{ asset('assets/images/icons/email.svg') }}"
                                            alt="img"></span>
                                    <input type="email" name="email" id="emailInput" class="form-control w-100 dynamictext"
                                        value="{{ old('email') }}" placeholder="{{ __('Enter your Email') }}">
                                </div>
                            </div>

                            <div class="mb-1">
                                <label for="passwordInput" class="form-label mb-0"
                                    style="font-size: 13px; font-weight: 500;">{{ __("Password") }}</label>
                                <div class="input-group">
                                    <span class="input-icon"><img src="{{ asset('assets/images/icons/lock.svg') }}"
                                            alt="img"></span>
                                    <input type="password" name="password" id="passwordInput"
                                        class="form-control w-100 dynamictext"
                                        placeholder="{{ __('Enter your Password') }}">
                                </div>
                            </div>

                            <div class="mb-1">
                                <label for="passwordConfirmationInput" class="form-label mb-0"
                                    style="font-size: 13px; font-weight: 500;">{{ __("Confirm Password") }}</label>
                                <div class="input-group">
                                    <span class="input-icon"><img src="{{ asset('assets/images/icons/lock.svg') }}"
                                            alt="img"></span>
                                    <input type="password" name="password_confirmation" id="passwordConfirmationInput"
                                        class="form-control w-100 dynamictext"
                                        placeholder="{{ __('Confirm your Password') }}">
                                </div>
                            </div>

                            <input type="hidden" name="company_code" id="business_code_hidden" value="">

                            <div class="d-flex justify-content-between align-items-center mb-0 mt-2"
                                style="margin-bottom: 0px !important;">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="terms" id="termsCheckbox"
                                        value="1" style="margin-top: 0.3rem;" {{ old('terms') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="termsCheckbox"
                                        style="color: #000000; vertical-align: top;">
                                        {{ __('I agree to the') }} <a href="#" class="text-primary fw-medium"
                                            data-bs-toggle="modal"
                                            data-bs-target="#termsModal">{{ __('Terms and Conditions') }}</a>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn login-btn submit-btn" id="registerBtn"
                                style="margin-top: 10px !important;">
                                <span id="registerBtnText">{{ __('Register') }}</span>
                            </button>
                        </form>

                        <div class="text-center mt-0" style="margin-top: 2px !important;">
                            <a class="backhome d-inline-block" href="{{ url('/') }}"
                                style="margin-bottom: 0px !important;">{{ __('Back to Home') }}</a>
                            <p class="mb-0">{{ __("Already have an account?") }} <a href="{{ route('login') }}"
                                    class="text-primary fw-medium">{{ __('Login') }}</a></p>
                        </div>
                    </div>
                </div>
                <div class="login-img">
                    <img src="{{ asset(get_option('general')['login_page_img'] ?? 'assets/images/login/login-avatar.png') }}"
                        alt="">
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" data-model="Register" id="auth">

    <!-- Terms & Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">{{ __('Terms & Conditions') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('Welcome to our site. By registering, you agree to the following terms and conditions:') }}</p>
                    <ol>
                        <li>{{ __('Use the site responsibly and follow all applicable laws.') }}</li>
                        <li>{{ __('Respect other users and do not post abusive or illegal content.') }}</li>
                        <li>{{ __('We may modify these terms at any time; continued use constitutes acceptance.') }}</li>
                    </ol>
                    <p class="mt-3">{{ __('If you have any questions, contact support.') }}</p>
                </div>
                <div class="modal-footer">
                    <div class="w-100 d-flex align-items-center justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
                            {{ __('Close') }}
                        </button>
                        <button type="button" id="agreeTermsBtn" class="btn btn-primary">
                            {{ __('I Agree') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Code Modal -->
    <div class="modal fade" id="businessCodeModal" tabindex="-1" aria-labelledby="businessCodeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="businessCodeModalLabel">{{ __('Enter Business Code') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2">{{ __('Please enter your 10-character business code.') }}</p>
                    <div class="mb-2">
                        <label for="business_code_input" class="form-label">{{ __('Business Code') }}</label>
                        <input type="text" maxlength="10" class="form-control" id="business_code_input"
                            placeholder="XXXXXXXXXX" style="text-transform: uppercase;" />
                        <div id="businessCodeError" class="invalid-feedback" style="display:none;"></div>
                        <div id="businessCodeSuccess" class="valid-feedback" style="display:none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100 d-flex align-items-center justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" id="verifyBusinessCodeBtn" class="btn btn-primary">
                            {{ __('Verify Code') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/auth.js') }}"></script>
    <script>
        // Title Case Helper (from common_js)
        function toCamelWords(str) {
            if (str == null) return '';
            var s = String(str);
            function isBoundary(ch) { return /[\s\-_/\.]/.test(ch); }
            function isLetter(ch) {
                try { return /\p{L}/u.test(ch); } catch (_) { return /[A-Za-z]/.test(ch); }
            }
            function isLower(ch) { return ch === ch.toLocaleLowerCase() && ch !== ch.toLocaleUpperCase(); }
            var out = '';
            var afterBoundary = true;
            for (var i = 0; i < s.length; i++) {
                var ch = s[i];
                if (afterBoundary && isLetter(ch) && isLower(ch)) {
                    out += ch.toLocaleUpperCase();
                } else {
                    out += ch;
                }
                afterBoundary = isBoundary(ch);
            }
            return out;
        }

        const nameInput = document.getElementById('nameInput');
        if (nameInput) {
            nameInput.addEventListener('input', function () {
                const start = this.selectionStart, end = this.selectionEnd;
                const v = this.value || '';
                const t = toCamelWords(v);
                if (v !== t) {
                    this.value = t;
                    try { this.setSelectionRange(start, end); } catch (_) { }
                }
            });
        }
    </script>
    @if ($errors->any())
        <script>
            try { Notify('error', null, @json($errors->first())); } catch (e) { }

            setTimeout(function () {
                var name = document.querySelector('input[name="name"]');
                if (name instanceof HTMLElement) {
                    try { name.focus({ preventScroll: true }); if (name.select) name.select(); } catch (_) { }
                }
            }, 500);
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('registerForm');
            const nameInput = document.getElementById('nameInput');
            const emailInput = document.getElementById('emailInput');
            const passwordInput = document.getElementById('passwordInput');
            const passwordConfirmationInput = document.getElementById('passwordConfirmationInput');
            const termsCheckbox = document.getElementById('termsCheckbox');
            const hiddenBusinessCode = document.getElementById('business_code_hidden');
            const registerBtn = document.getElementById('registerBtn');
            const registerBtnText = document.getElementById('registerBtnText');
            let businessValidated = false;

            function showError(message) {
                try { Notify('error', null, message); } catch (e) { }
            }

            // Terms modal agree button
            const agreeBtn = document.getElementById('agreeTermsBtn');
            if (agreeBtn) {
                agreeBtn.addEventListener('click', function () {
                    if (termsCheckbox) termsCheckbox.checked = true;
                    const modalEl = document.getElementById('termsModal');
                    if (typeof bootstrap !== 'undefined' && modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();
                    }
                });
            }

            // Form submission
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (!nameInput || nameInput.value.trim() === '') {
                        showError('{{ __("Please enter your Username.") }}');
                        try { nameInput.focus(); } catch (_) { }
                        return;
                    }

                    if (!emailInput || emailInput.value.trim() === '') {
                        showError('{{ __("Please enter your email.") }}');
                        try { emailInput.focus(); } catch (_) { }
                        return;
                    }

                    if (!passwordInput || passwordInput.value.trim() === '') {
                        showError('{{ __("Please enter your password.") }}');
                        try { passwordInput.focus(); } catch (_) { }
                        return;
                    }

                    if (!passwordConfirmationInput || passwordConfirmationInput.value.trim() === '') {
                        showError('{{ __("Please confirm your password.") }}');
                        try { passwordConfirmationInput.focus(); } catch (_) { }
                        return;
                    }

                    if (passwordInput.value !== passwordConfirmationInput.value) {
                        showError('{{ __("Passwords do not match.") }}');
                        try { passwordConfirmationInput.focus(); } catch (_) { }
                        return;
                    }

                    if (!termsCheckbox || !termsCheckbox.checked) {
                        showError('{{ __("Please accept the Terms and Conditions.") }}');
                        return;
                    }

                    // If business already validated, submit now
                    if (businessValidated && hiddenBusinessCode && hiddenBusinessCode.value.length === 10) {
                        if (registerBtn && registerBtnText) {
                            registerBtnText.textContent = '{{ __("Registering...") }}';
                            registerBtn.disabled = true;
                        }
                        form.submit();
                        return;
                    }

                    // Otherwise, show business code modal
                    const modalEl = document.getElementById('businessCodeModal');
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    const codeInput = document.getElementById('business_code_input');
                    const errEl = document.getElementById('businessCodeError');
                    const okEl = document.getElementById('businessCodeSuccess');
                    if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }
                    if (okEl) { okEl.style.display = 'none'; okEl.textContent = ''; }
                    if (codeInput) { codeInput.value = ''; setTimeout(() => codeInput.focus(), 300); }
                    modal.show();
                });
            }

            // Uppercase enforce on business code input
            const codeInput = document.getElementById('business_code_input');
            if (codeInput) {
                codeInput.addEventListener('input', function () {
                    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10);
                });
                codeInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const btn = document.getElementById('verifyBusinessCodeBtn');
                        if (btn) btn.click();
                    }
                });
            }

            // Verify business code click
            const verifyBtn = document.getElementById('verifyBusinessCodeBtn');
            if (verifyBtn) {
                verifyBtn.addEventListener('click', function () {
                    const code = (document.getElementById('business_code_input')?.value || '').toUpperCase();
                    const errEl = document.getElementById('businessCodeError');
                    const okEl = document.getElementById('businessCodeSuccess');
                    if (!code || code.length !== 10) {
                        if (errEl) { errEl.textContent = '{{ __("Please enter a 10-character code.") }}'; errEl.style.display = 'block'; }
                        return;
                    }

                    // Call backend to validate
                    fetch("{{ route('validate.business.code') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ company_code: code })
                    }).then(async function (res) {
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok || data.valid === false) {
                            const msg = data && data.message ? data.message : '{{ __("Invalid business code.") }}';
                            if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
                            if (okEl) { okEl.style.display = 'none'; }
                            const inp = document.getElementById('business_code_input');
                            if (inp) { inp.focus(); inp.select(); }
                            return;
                        }

                        // Success: set hidden and proceed to submit
                        if (okEl) { okEl.textContent = '{{ __("Code verified.") }}'; okEl.style.display = 'block'; }
                        if (errEl) { errEl.style.display = 'none'; }
                        if (hiddenBusinessCode) hiddenBusinessCode.value = code;
                        businessValidated = true;

                        const modalEl = document.getElementById('businessCodeModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) { modal.hide(); }

                        // Now submit the form programmatically
                        if (registerBtn && registerBtnText) {
                            registerBtnText.textContent = '{{ __("Registering...") }}';
                            registerBtn.disabled = true;
                        }
                        form.submit();
                    }).catch(function (err) {
                        if (errEl) { errEl.textContent = '{{ __("Could not validate code. Please try again.") }}'; errEl.style.display = 'block'; }
                        if (okEl) { okEl.style.display = 'none'; }
                    });
                });
            }

            // Reset button state if modal is closed without verifying
            const businessModalEl = document.getElementById('businessCodeModal');
            if (businessModalEl) {
                businessModalEl.addEventListener('shown.bs.modal', function () {
                    const inp = document.getElementById('business_code_input');
                    if (inp) { inp.focus(); inp.select(); }
                });
                businessModalEl.addEventListener('hidden.bs.modal', function () {
                    if (registerBtn && registerBtnText) {
                        registerBtnText.textContent = '{{ __("Register") }}';
                        registerBtn.disabled = false;
                    }
                });
            }
        });
    </script>
@endpush