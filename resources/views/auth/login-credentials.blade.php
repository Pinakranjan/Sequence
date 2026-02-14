@extends('layouts.auth.app')

@section('title')
    {{ __('Enter Credentials') }}
@endsection

@push('css')
    <style>
        .pin-input-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 15px 0;
        }

        .pin-input {
            width: 55px;
            height: 60px;
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

        .auth-method-toggle {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .auth-method-btn {
            padding: 8px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .auth-method-btn.active {
            border-color: #3b82f6;
            background: #3b82f6;
            color: white;
        }

        .auth-method-btn:hover:not(.active) {
            border-color: #9ca3af;
        }

        .credentials-section {
            display: none;
        }

        .credentials-section.active {
            display: block;
        }

        #lock-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        #lock-overlay .lock-overlay-card {
            background: #ff7a2a;
            color: #fff;
            padding: 10px 16px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 10px;
            min-width: 220px;
            max-width: 80%;
            width: auto;
            height: auto;
            justify-content: center;
        }

        #lock-overlay .lock-overlay-spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.95);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin .8s linear infinite;
            flex: 0 0 auto;
        }

        #lock-overlay .lock-overlay-card .msg {
            color: #fff;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .pin-input.success {
            background-color: #d1fae5 !important;
            border-color: #10b981 !important;
            color: #065f46 !important;
        }

        .pin-input.error {
            background-color: #fee2e2 !important;
            border-color: #ef4444 !important;
            color: #991b1b !important;
        }

        .user-email-display {
            background: transparent;
            padding: 0;
            border-radius: 0;
            margin-bottom: 20px;
            text-align: center;
        }

        .lock-avatar {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            object-fit: cover;
        }

        :root {
            --bs-secondary: #8c57d1;
            --bs-secondary-rgb: 140, 87, 209;
            --bs-success: #29aa85;
            --bs-success-rgb: 41, 170, 133;
            --bs-danger: #ec8290;
            --bs-danger-rgb: 236, 130, 144;
            --bs-warning: #fc8019;
            --bs-warning-rgb: 252, 128, 25;
        }

        .login-body .badge.bg-secondary {
            background-color: rgba(var(--bs-secondary-rgb), var(--bs-bg-opacity, 1)) !important;
            color: #fff !important;
        }

        .login-body .badge.bg-success {
            background-color: rgba(var(--bs-success-rgb), var(--bs-bg-opacity, 1)) !important;
            color: #fff !important;
        }

        .login-body .badge.bg-danger {
            background-color: rgba(var(--bs-danger-rgb), var(--bs-bg-opacity, 1)) !important;
            color: #fff !important;
        }

        .login-body .badge.bg-warning {
            background-color: rgba(var(--bs-warning-rgb), var(--bs-bg-opacity, 1)) !important;
            color: #fff !important;
        }
    </style>
@endpush

@section('main_content')
    <div class="footer position-relative">
        <div class="mybazar-login-section ">
            <div class="mybazar-login-wrapper ">
                <div class="login-wrapper">
                    <div class="login-body w-100">
                        <div class="footer-logo w-100">
                            <img src="{{ asset(get_option('general')['login_page_logo'] ?? 'assets/images/icons/logo.svg') }}"
                                alt="logo">
                        </div>
                        <h2 class="login-title">{{ __('Enter Your Credentials') }}</h2>
                        <h6 class="login-para mb-2">{{ __('Please enter your password or PIN to continue') }}</h6>

                        @php
                            $userRoleRaw = $user->role ?? 'User';
                            $roleLabel = match (strtolower((string) $userRoleRaw)) {
                                'super admin' => 'Super Admin',
                                'admin' => 'Admin',
                                default => 'User',
                            };
                            $roleBadgeClass = match ($roleLabel) {
                                'Super Admin' => 'bg-danger',
                                'Admin' => 'bg-success',
                                default => 'bg-secondary',
                            };
                            $avatarUrl = url('upload/no_image.jpg');
                            $raw = $user->photo ?? null;
                            if (!empty($raw)) {
                                $key = 'upload/user_images/' . ltrim((string) $raw, '/');
                                if ((bool) env('AWS_SIGNED_URLS', false)) {
                                    try {
                                        $disk = \Illuminate\Support\Facades\Storage::disk('s3');
                                        if (is_callable([$disk, 'temporaryUrl'])) {
                                            $avatarUrl = (string) call_user_func([$disk, 'temporaryUrl'], $key, now()->addMinutes((int) env('AWS_SIGNED_URL_TTL', 60)));
                                        } else {
                                            $avatarUrl = url($key);
                                        }
                                    } catch (\Throwable $e) {
                                        $avatarUrl = url($key);
                                    }
                                } else {
                                    $awsBase = config('filesystems.disks.s3.url') ?? env('AWS_URL');
                                    if ($awsBase) {
                                        $avatarUrl = rtrim($awsBase, '/') . '/' . ltrim($key, '/');
                                    } else {
                                        $avatarUrl = url($key);
                                    }
                                }
                            }
                        @endphp
                        <div class="user-email-display d-flex flex-column align-items-center gap-2 mb-3">
                            <img class="lock-avatar" src="{{ $avatarUrl }}" alt="avatar" />
                            <div class="fw-semibold d-flex align-items-center gap-2" style="font-size: 1.25rem;">
                                {{ $user->name ?? 'User' }}
                                <span class="badge {{ $roleBadgeClass }} text-uppercase"
                                    style="font-size: 0.55rem; line-height: 1;">{{ $roleLabel }}</span>
                            </div>
                            <div class="text-muted small">{{ $email }}</div>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger mt-3">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($hasPinEnabled)
                            <div class="auth-method-toggle d-flex justify-content-center gap-2 mb-3">
                                <button type="button" class="btn btn-sm auth-method-btn active" data-method="pin"
                                    style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 4px 12px; font-size: 13px; background-color: #ff7a2a; color: #fff;">
                                    {{ __('PIN') }}
                                </button>
                                <button type="button" class="btn btn-sm auth-method-btn" data-method="password"
                                    style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 4px 12px; font-size: 13px; background-color: transparent; color: inherit;">
                                    {{ __('Password') }}
                                </button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.credentials.validate') }}" id="credentialsForm">
                            @csrf
                            <input type="hidden" name="auth_method" id="authMethodInput"
                                value="{{ $hasPinEnabled ? 'pin' : 'password' }}">

                            <!-- Password Section -->
                            <div class="credentials-section {{ !$hasPinEnabled ? 'active' : '' }}" id="passwordSection">
                                <div class="input-group">
                                    <span class="input-icon"><img src="{{ asset('assets/images/icons/lock.svg') }}"
                                            alt="img"></span>
                                    <span class="hide-pass">
                                        <img src="{{ asset('assets/images/icons/show.svg') }}" alt="img">
                                        <img src="{{ asset('assets/images/icons/Hide.svg') }}" alt="img">
                                    </span>
                                    <input type="password" name="password" id="passwordInput"
                                        class="form-control w-100 password" placeholder="{{ __('Enter your Password') }}">
                                </div>
                            </div>

                            <!-- PIN Section -->
                            @if($hasPinEnabled)
                                <div class="credentials-section active" id="pinSection">
                                    <div class="pin-input-container d-flex justify-content-center gap-2 mb-3">
                                        <input type="text" maxlength="1" class="form-control pin-input text-center"
                                            data-index="0" inputmode="numeric"
                                            style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                        <input type="text" maxlength="1" class="form-control pin-input text-center"
                                            data-index="1" inputmode="numeric"
                                            style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                        <input type="text" maxlength="1" class="form-control pin-input text-center"
                                            data-index="2" inputmode="numeric"
                                            style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                        <input type="text" maxlength="1" class="form-control pin-input text-center"
                                            data-index="3" inputmode="numeric"
                                            style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                    </div>
                                    <input type="hidden" name="pin" id="pinHiddenInput" value="">
                                </div>
                            @endif

                            <button type="submit" class="btn login-btn submit-btn" id="loginBtn">{{ __('Log In') }}</button>
                        </form>

                        <div class="d-flex justify-content-between mt-0">
                            <a class="backhome text-muted" href="{{ url('/') }}"
                                style="font-size: 0.85rem;">{{ __('Back to Home') }}</a>
                            <a href="#" class="backhome text-danger" id="backToLoginBtn"
                                style="font-size: 0.85rem;">{{ __('Use different account') }}</a>
                        </div>

                        <div id="lock-overlay" style="display:none;">
                            <div class="lock-overlay-card">
                                <div class="lock-overlay-spinner"></div>
                                <div class="msg">Verifying…</div>
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
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const authMethodBtns = document.querySelectorAll('.auth-method-btn');
            const authMethodInput = document.getElementById('authMethodInput');
            const passwordSection = document.getElementById('passwordSection');
            const pinSection = document.getElementById('pinSection');
            const passwordInput = document.getElementById('passwordInput');
            const pinInputs = document.querySelectorAll('.pin-input');
            const pinHiddenInput = document.getElementById('pinHiddenInput');
            const backToLoginBtn = document.getElementById('backToLoginBtn');
            const form = document.getElementById('credentialsForm');
            const overlay = document.getElementById('lock-overlay');

            function showOverlay(msg) {
                if (overlay) {
                    overlay.querySelector('.msg').textContent = msg || 'Verifying…';
                    overlay.style.display = 'flex';
                }
            }
            function hideOverlay() {
                if (overlay) overlay.style.display = 'none';
            }

            // Initial focus
            const currentMethod = authMethodInput.value;
            if (currentMethod === 'pin' && pinInputs[0]) {
                pinInputs[0].focus();
            } else if (passwordInput) {
                passwordInput.focus();
            }

            // Switch between password and PIN
            authMethodBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const method = this.dataset.method;

                    authMethodBtns.forEach(b => {
                        b.classList.remove('active');
                        b.style.backgroundColor = 'transparent';
                        b.style.color = 'inherit';
                    });
                    this.classList.add('active');
                    this.style.backgroundColor = '#ff7a2a';
                    this.style.color = '#fff';

                    authMethodInput.value = method;

                    if (method === 'password') {
                        passwordSection.classList.add('active');
                        if (pinSection) pinSection.classList.remove('active');
                        if (passwordInput) passwordInput.focus();
                    } else {
                        passwordSection.classList.remove('active');
                        if (pinSection) {
                            pinSection.classList.add('active');
                            if (pinInputs[0]) pinInputs[0].focus();
                        }
                    }
                });
            });

            // Trigger password switch if PIN fails
            function switchToPassword() {
                const pwdBtn = document.querySelector('.auth-method-btn[data-method="password"]');
                if (pwdBtn) pwdBtn.click();
            }

            // PIN input handling
            if (pinInputs.length > 0) {
                function updatePinHidden() {
                    let pin = '';
                    pinInputs.forEach(input => {
                        pin += input.value;
                    });
                    pinHiddenInput.value = pin;
                }

                pinInputs.forEach((input, index) => {
                    input.addEventListener('input', function () {
                        const value = this.value;
                        if (!/^\d*$/.test(value)) { this.value = ''; return; }

                        pinInputs.forEach(inp => inp.classList.remove('success', 'error'));
                        updatePinHidden();

                        if (value.length === 1 && index < pinInputs.length - 1) {
                            pinInputs[index + 1].focus();
                        }

                        if (index === pinInputs.length - 1 && pinHiddenInput.value.length === 4) {
                            setTimeout(() => form.dispatchEvent(new Event('submit')), 300);
                        }
                    });

                    input.addEventListener('keydown', function (e) {
                        if (e.key === 'Backspace' && !this.value && index > 0) {
                            pinInputs[index - 1].focus();
                            pinInputs[index - 1].value = '';
                            updatePinHidden();
                        }
                    });

                    input.addEventListener('paste', function (e) {
                        e.preventDefault();
                        const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 4);
                        if (pastedData) {
                            pastedData.split('').forEach((char, i) => {
                                if (pinInputs[i]) pinInputs[i].value = char;
                            });
                            updatePinHidden();
                            const nextIndex = Math.min(pastedData.length, pinInputs.length - 1);
                            pinInputs[nextIndex].focus();
                            if (pastedData.length === 4) {
                                setTimeout(() => form.dispatchEvent(new Event('submit')), 300);
                            }
                        }
                    });

                    input.addEventListener('focus', function () { this.select(); });
                });
            }

            // AJAX Form Submission
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const method = authMethodInput.value;

                    if (method === 'password' && passwordInput && !passwordInput.value.trim()) {
                        try { Notify('error', null, 'Please enter your password.'); } catch (e) { }
                        passwordInput.focus();
                        return;
                    }
                    if (method === 'pin' && pinHiddenInput.value.length < 4) {
                        try { Notify('error', null, 'Please enter complete 4-digit PIN.'); } catch (e) { }
                        if (pinInputs[0]) pinInputs[0].focus();
                        return;
                    }

                    showOverlay('Verifying…');
                    const MIN_MS = 1500;
                    const start = Date.now();
                    const formData = new FormData(form);

                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(res => res.text().then(t => {
                        try {
                            return JSON.parse(t);
                        } catch (e) {
                            if (res.redirected || res.ok) {
                                return { success: true, redirect: res.url || '/dashboard' };
                            }
                            throw new Error('Invalid response');
                        }
                    }))
                        .then(data => {
                            if (data.success || data.redirect) {
                                if (method === 'pin') pinInputs.forEach(inp => inp.classList.add('success'));
                                const wait = Math.max(500, MIN_MS - (Date.now() - start));
                                setTimeout(() => {
                                    window.location.href = data.redirect || '/dashboard';
                                }, wait);
                            } else {
                                throw new Error(data.message || 'Verification failed');
                            }
                        })
                        .catch(err => {
                            const msg = err && err.message ? err.message : 'Incorrect credentials. Please try again.';

                            // Show error state immediately while overlay is visible
                            if (method === 'pin') {
                                pinInputs.forEach(inp => inp.classList.add('error'));
                            }

                            // Keep overlay visible for MIN_MS to show error state
                            const wait = Math.max(500, MIN_MS - (Date.now() - start));
                            setTimeout(() => {
                                hideOverlay();
                                try { Notify('error', null, msg); } catch (e) { }

                                if (method === 'pin') {
                                    // Clear PIN and remove error state after overlay is gone
                                    pinInputs.forEach(inp => {
                                        inp.value = '';
                                        inp.classList.remove('error');
                                    });
                                    if (pinHiddenInput) pinHiddenInput.value = '';
                                    if (pinInputs[0]) pinInputs[0].focus();
                                } else {
                                    if (passwordInput) {
                                        passwordInput.value = '';
                                        passwordInput.focus();
                                    }
                                }
                            }, wait);
                        });
                });
            }

            // Back to login
            if (backToLoginBtn) {
                backToLoginBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    localStorage.removeItem('app_remembered_email');
                    window.location.href = '{{ route("login") }}';
                });
            }
        });
    </script>
@endpush