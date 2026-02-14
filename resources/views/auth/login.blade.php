@extends('layouts.auth.app')

@section('title')
    {{ __('Login') }}
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
                        <h2 class="login-title">{{ __('Welcome to') }} {{ __(env('APP_NAME')) }}</h2>
                        <h6 class="login-para">{{ __('Please enter your email to continue') }}</h6>

                        @if ($errors->any())
                            <div class="alert alert-danger mt-3">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.email') }}" id="loginEmailForm">
                            @csrf
                            <div class="input-group ">
                                <span class="input-icon"><img src="{{ asset('assets/images/icons/email.svg') }}"
                                        alt="img"></span>
                                <input type="text" name="email" id="emailInput" class="form-control w-100 dynamictext"
                                    value="{{ old('email') }}" placeholder="{{ __('Enter your Email') }}" autofocus>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-0 mt-2"
                                style="margin-bottom: 0px !important;">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="remember_email" id="rememberEmail"
                                        style="margin-top: 0.3rem;">
                                    <label class="form-check-label" for="rememberEmail"
                                        style="color: #000000; vertical-align: top;">
                                        {{ __('Remember me') }}
                                    </label>
                                </div>
                                <a href="{{ route('password.code.request') }}" id="forgotPasswordLink" class="backhome"
                                    style="font-size: 14px; color: #000000; margin-top: 0 !important; margin-bottom: 0 !important;">{{ __('Forgot Password?') }}</a>
                            </div>

                            <button type="submit" class="btn login-btn submit-btn"
                                style="margin-top: 10px !important;">{{ __('Continue') }}</button>
                        </form>

                        <div class="text-center mt-0" style="margin-top: 2px !important;">
                            <a class="backhome d-inline-block" href="{{ url('/') }}"
                                style="margin-bottom: 0px !important;">{{ __('Back to Home') }}</a>
                            <p class="mb-0">{{ __("Don't have an account?") }} <a href="{{ route('register') }}"
                                    class="text-primary fw-medium">{{ __('Sign Up') }}</a></p>
                        </div>

                        <div class="text-center mt-2" id="backToLoginContainer" style="display: none;">
                            <a href="#" class="backhome text-danger" id="clearRememberBtn">{{ __('Clear saved email') }}</a>
                        </div>

                        <div class="role-buttons-container p-0 mt-3">
                            <div class="d-flex flex-wrap gap-3 justify-content-between">
                                <button type="button" class="btn role-btn" data-email="admin@sequence.com"
                                    style="flex: 1 1 calc(33.33% - 0.75rem); color: #F06292; border: 1px solid #F06292; font-weight: 600; height:48px; display:flex; align-items:center; justify-content:center; padding:0 1rem;">{{ __('Super Admin') }}</button>
                                <button type="button" class="btn role-btn" data-email="dalmia@sequence.com"
                                    style="flex: 1 1 calc(33.33% - 0.75rem); color: #198754; border: 1px solid #198754; font-weight: 600; height:48px; display:flex; align-items:center; justify-content:center; padding:0 1rem;">{{ __('Admin') }}</button>
                                <button type="button" class="btn role-btn" data-email="manas@sequence.com"
                                    style="flex: 1 1 calc(33.33% - 0.75rem); color: #8c57ff; border: 1px solid #8c57ff; font-weight: 600; height:48px; display:flex; align-items:center; justify-content:center; padding:0 1rem;">{{ __('User') }}</button>
                            </div>
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
    <input type="hidden" data-model="Login" id="auth">
    <style>
        .role-buttons-container .role-btn {
            min-width: 0;
        }

        .addon-badge {
            position: absolute;
            top: -10px;
            left: 10px;
            background: #fff200;
            color: #111;
            font-size: 12px;
            font-weight: 500;
            padding: 3px 6px;
            border-radius: 4px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            display: inline-block;
            line-height: 1;
            z-index: 10;
        }
    </style>
@endsection




@push('js')
    <script src="{{ asset('assets/js/auth.js') }}"></script>
    @if ($errors->any())
        <script>
            try { Notify('error', null, @json($errors->first())); } catch (e) { }

            setTimeout(function () {
                var email = document.querySelector('input[name="email"]');
                if (email instanceof HTMLElement) {
                    try { email.focus({ preventScroll: true }); if (email.select) email.select(); } catch (_) { }
                }
            }, 500);
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const emailInput = document.getElementById('emailInput');
            const rememberCheckbox = document.getElementById('rememberEmail');
            const backToLoginContainer = document.getElementById('backToLoginContainer');
            const clearRememberBtn = document.getElementById('clearRememberBtn');
            const form = document.getElementById('loginEmailForm');

            // Check if email is saved in localStorage
            const savedEmail = localStorage.getItem('app_remembered_email');
            if (savedEmail) {
                emailInput.value = savedEmail;
                rememberCheckbox.checked = true;
                backToLoginContainer.style.display = 'block';
            }

            // Save/remove email on form submit based on checkbox
            form.addEventListener('submit', function (e) {
                if (rememberCheckbox.checked && emailInput.value.trim()) {
                    localStorage.setItem('app_remembered_email', emailInput.value.trim().toLowerCase());
                } else {
                    localStorage.removeItem('app_remembered_email');
                }
            });

            // Clear Remember button
            clearRememberBtn.addEventListener('click', function (e) {
                e.preventDefault();
                localStorage.removeItem('app_remembered_email');
                emailInput.value = '';
                rememberCheckbox.checked = false;
                backToLoginContainer.style.display = 'none';
                emailInput.focus();
                try { Notify('info', null, 'Saved email cleared.'); } catch (e) { }
            });

            // Handle Forgot Password link - bypass email step if field is filled
            const forgotPasswordLink = document.getElementById('forgotPasswordLink');
            if (forgotPasswordLink) {
                forgotPasswordLink.addEventListener('click', function (e) {
                    const emailField = document.getElementById('emailInput');
                    const emailValue = emailField ? emailField.value.trim() : '';

                    if (emailValue) {
                        e.preventDefault();
                        // Create a temporary form to POST to the forgotEmail route
                        const tempForm = document.createElement('form');
                        tempForm.method = 'POST';
                        tempForm.action = '{{ route("password.code.email") }}';

                        const tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        tokenInput.value = '{{ csrf_token() }}';
                        tempForm.appendChild(tokenInput);

                        const emailInputHidden = document.createElement('input');
                        emailInputHidden.type = 'hidden';
                        emailInputHidden.name = 'email';
                        emailInputHidden.value = emailValue;
                        tempForm.appendChild(emailInputHidden);

                        document.body.appendChild(tempForm);
                        tempForm.submit();
                    }
                    // if empty, let it go to the standard forgot password page
                });
            }

            // Role buttons - fill email
            document.querySelectorAll('.role-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var email = this.dataset.email || '';
                    if (emailInput) {
                        emailInput.value = email;
                        emailInput.focus();
                    }
                });
            });

            // Hover effects for role buttons
            document.querySelectorAll('.role-btn').forEach(function (btn) {
                btn.addEventListener('mouseenter', function () {
                    this.style.backgroundColor = this.style.color;
                    this.style.color = '#fff';
                });
                btn.addEventListener('mouseleave', function () {
                    var borderColor = this.style.borderColor;
                    this.style.color = borderColor;
                    this.style.backgroundColor = 'transparent';
                });
            });
        });
    </script>
@endpush