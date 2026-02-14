@extends('layouts.auth.app')

@section('title')
    {{ __('Reset Password') }}
@endsection

@push('css')
    <style>
        .pin-input-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 10px 0 20px;
        }

        .pin-input {
            width: 50px;
            height: 55px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .pin-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .pin-input:hover {
            border-color: #9ca3af;
        }

        /* Success/Error visual state for PIN boxes: border + text color, no shadow */
        .pin-input.success {
            border-color: #10b981 !important;
            color: #10b981;
            box-shadow: none !important;
        }

        .pin-input.error {
            border-color: #ef4444 !important;
            color: #ef4444;
            box-shadow: none !important;
        }

        /* Inline Alert Styles (parity with verify page) */
        .verification-alert {
            padding: 12px 16px;
            padding-bottom: 8px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
            flex-direction: column;
            font-size: 14px;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .verification-alert.show {
            display: flex;
            opacity: 1;
            transform: translateY(0);
        }

        .verification-alert.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .verification-alert.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .verification-alert.info {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }

        .alert-body {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .alert-icon {
            font-size: 20px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .alert-message {
            font-size: 13px;
            opacity: 0.9;
        }

        .timer-bar {
            width: 100%;
            height: 3px;
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 2px;
            overflow: hidden;
            margin-top: auto;
        }

        .timer-progress {
            height: 100%;
            background-color: currentColor;
            width: 0%;
            transition: width linear;
        }

        .spinner-small {
            width: 16px;
            height: 16px;
            border: 2px solid currentColor;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@section('main_content')
    <div class="footer position-relative">
        <div class="mybazar-login-section auth-compact-banners">
            <div class="mybazar-login-wrapper ">
                <div class="login-wrapper">
                    <div class="login-body w-100">
                        <div class="footer-logo w-100">
                            <img src="{{ asset(get_option('general')['login_page_logo'] ?? 'assets/images/icons/logo.svg') }}"
                                alt="logo">
                        </div>

                        <h2 class="login-title">{{ __('Reset Password') }}</h2>
                        <h6 class="login-para">{{ __('Enter the 6-digit code sent to your email') }}</h6>

                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        {{-- Dev-only helper: show verification code locally to speed up testing
                        Persist across validation errors by reading from cache using the email param.
                        --}}
                        @php
                            $devShow = (bool) config('app.show_dev_codes');
                            $devCode = null;
                            if ($devShow) {
                                $devCode = session('password_reset_code');
                                if (!$devCode) {
                                    $emailParam = strtolower((string) request('email'));
                                    if ($emailParam) {
                                        $devCode = cache()->get('password_reset_code:' . $emailParam);
                                    }
                                }
                            }
                        @endphp
                        @if($devShow && $devCode)
                            <div class="alert alert-info" role="alert">
                                <strong>{{ __('Development mode:') }}</strong>
                                {{ __('Your password reset code is') }}
                                <span class="badge bg-primary" style="font-size:14px;">{{ $devCode }}</span>.
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.code.update') }}" class="my-0" id="resetForm">
                            @csrf
                            <input type="hidden" name="email" value="{{ request('email') }}">

                            <div class="form-group mb-2">
                                <label class="form-label w-100 text-center fw-bold">{{ __('Verification Code') }}</label>
                                <div class="pin-input-container">
                                    <input type="text" maxlength="1" class="form-control pin-input" data-index="0"
                                        autocomplete="one-time-code" inputmode="numeric">
                                    <input type="text" maxlength="1" class="form-control pin-input" data-index="1"
                                        autocomplete="one-time-code" inputmode="numeric">
                                    <input type="text" maxlength="1" class="form-control pin-input" data-index="2"
                                        autocomplete="one-time-code" inputmode="numeric">
                                    <input type="text" maxlength="1" class="form-control pin-input" data-index="3"
                                        autocomplete="one-time-code" inputmode="numeric">
                                    <input type="text" maxlength="1" class="form-control pin-input" data-index="4"
                                        autocomplete="one-time-code" inputmode="numeric">
                                    <input type="text" maxlength="1" class="form-control pin-input" data-index="5"
                                        autocomplete="one-time-code" inputmode="numeric">
                                </div>
                                <input type="hidden" name="code" id="code" value="">
                                @error('code')
                                    <div class="text-center">
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            <div class="verification-alert" id="verificationAlert">
                                <div class="alert-body">
                                    <div class="alert-icon" id="alertIcon"></div>
                                    <div class="alert-content">
                                        <div class="alert-title" id="alertTitle"></div>
                                        <div class="alert-message" id="alertMessage"></div>
                                    </div>
                                </div>
                            </div>

                            <button class="btn login-btn submit-btn" type="submit"
                                id="submitBtn">{{ __('Verify & Reset Password') }}</button>
                        </form>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a class="backhome" href="{{ route('login') }}">{{ __('Back to Login') }}</a>
                        <a class="backhome" href="{{ url('/') }}">{{ __('Back Home') }}</a>
                    </div>
                </div>

                <div class="login-img">
                    <img src="{{ asset(get_option('general')['login_page_img'] ?? 'assets/images/login/login-avatar.png') }}"
                        alt="">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @if ($errors->any())
        <script>
            try { Notify('error', null, @json($errors->first())); } catch (e) { }
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pinInputs = document.querySelectorAll('.pin-input');
            const hiddenInput = document.getElementById('code');
            const form = document.getElementById('resetForm');
            const submitButton = document.getElementById('submitBtn');
            const alertBox = document.getElementById('verificationAlert');
            const alertIcon = document.getElementById('alertIcon');
            const alertTitle = document.getElementById('alertTitle');
            const alertMessage = document.getElementById('alertMessage');
            let isSubmitting = false;

            function setPinState(state) {
                pinInputs.forEach(inp => {
                    inp.classList.remove('success', 'error');
                    if (state === 'success') inp.classList.add('success');
                    if (state === 'error') inp.classList.add('error');
                });
            }

            function showAlert(type, title, message, duration = 0) {
                alertBox.className = 'verification-alert ' + type;
                alertBox.style.display = 'flex';

                alertIcon.innerHTML = (type === 'success') ? '✓' : (type === 'error') ? '✕' : '<div class="spinner-small"></div>';
                alertTitle.textContent = title;
                alertMessage.textContent = message;

                // Remove any existing timer bar
                const existingTimer = alertBox.querySelector('.timer-bar');
                if (existingTimer) existingTimer.remove();

                if (duration > 0) {
                    const timerBar = document.createElement('div');
                    timerBar.className = 'timer-bar';
                    const timerProgress = document.createElement('div');
                    timerProgress.className = 'timer-progress';
                    timerProgress.style.transition = `width ${duration}ms linear`;
                    timerBar.appendChild(timerProgress);
                    alertBox.appendChild(timerBar);

                    if (type === 'success') {
                        requestAnimationFrame(() => { requestAnimationFrame(() => { timerProgress.style.width = '100%'; }); });
                    } else {
                        timerProgress.style.width = '100%';
                        requestAnimationFrame(() => { requestAnimationFrame(() => { timerProgress.style.width = '0%'; }); });
                    }
                }

                setTimeout(() => { alertBox.classList.add('show'); }, 10);

                // Apply pin input visual state
                if (type === 'success') setPinState('success');
                else if (type === 'error') setPinState('error');
                else setPinState(null);
            }

            function hideAlert() {
                // Start hide transition
                alertBox.classList.remove('show');
                // After transition, collapse the space
                setTimeout(() => { alertBox.style.display = 'none'; }, 200);
                // Reset PIN visuals when message is closed
                setPinState(null);
            }

            function updateHiddenInput() {
                let code = '';
                pinInputs.forEach(input => { code += input.value; });
                hiddenInput.value = code;

                // Auto-submit when all 6 digits entered
                if (hiddenInput.value.length === 6 && form && !isSubmitting) {
                    setTimeout(() => submitForm(), 200);
                }
            }

            function submitForm() {
                if (isSubmitting) return;
                if (hiddenInput.value.length !== 6) {
                    showAlert('error', 'Invalid Code', 'Please enter all 6 digits of the verification code.', 2500);
                    // Hide message after duration, reset visuals, and refocus input
                    setTimeout(() => { hideAlert(); pinInputs[0]?.focus(); }, 2500);
                    return;
                }

                isSubmitting = true;
                submitButton.disabled = true;
                pinInputs.forEach(i => i.disabled = true);

                // Show verifying info (decremental bar)
                showAlert('info', 'Verifying…', 'Please wait while we confirm your code.', 1500);

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        return response.text().then(text => {
                            try { return JSON.parse(text); }
                            catch (e) {
                                if (response.redirected || response.ok) {
                                    return { success: true, redirect: response.url || '/dashboard' };
                                }
                                throw new Error('Invalid response');
                            }
                        });
                    })
                    .then(data => {
                        if (data.success || data.redirect) {
                            // Success state with incremental bar
                            showAlert('success', 'Verification Successful!', 'Redirecting to dashboard…', 2000);
                            setTimeout(() => { window.location.href = data.redirect || '/dashboard'; }, 2000);
                        } else {
                            throw new Error(data.message || 'Verification failed');
                        }
                    })
                    .catch(err => {
                        showAlert('error', 'Verification Failed', err.message || 'Invalid verification code. Please try again.', 2000);
                        setTimeout(() => {
                            hideAlert();
                            isSubmitting = false;
                            submitButton.disabled = false;
                            pinInputs.forEach(input => { input.disabled = false; input.value = ''; input.classList.remove('success', 'error'); });
                            hiddenInput.value = '';
                            pinInputs[0]?.focus();
                        }, 2000);
                    });
            }

            pinInputs.forEach((input, index) => {
                input.addEventListener('input', function () {
                    const value = this.value;
                    if (!/^\d*$/.test(value)) { this.value = ''; return; }
                    // Reset visuals to default on entry
                    this.classList.remove('success', 'error');
                    updateHiddenInput();
                    if (value.length === 1 && index < pinInputs.length - 1) {
                        pinInputs[index + 1].focus();
                    }
                });

                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        pinInputs[index - 1].focus();
                        pinInputs[index - 1].value = '';
                        updateHiddenInput();
                    }
                });

                input.addEventListener('paste', function (e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/\D/g, '');
                    if (pastedData) {
                        for (let i = 0; i < pastedData.length && index + i < pinInputs.length; i++) {
                            pinInputs[index + i].value = pastedData[i];
                        }
                        const nextIndex = Math.min(index + pastedData.length, pinInputs.length - 1);
                        pinInputs[nextIndex].focus();
                        updateHiddenInput();
                    }
                });

                input.addEventListener('focus', function () { this.select(); this.classList.remove('success', 'error'); });
            });

            if (pinInputs.length > 0) { pinInputs[0].focus(); }

            // Focus first invalid input if any server-side validation errors
            const firstInvalid = document.querySelector('.is-invalid');
            if (firstInvalid instanceof HTMLElement) {
                firstInvalid.focus();
            }

            // Intercept manual submit
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitForm();
            });
        });
    </script>
@endpush