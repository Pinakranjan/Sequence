@extends('layouts.auth.app')

@section('title', __('Lock Screen'))

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
            background:
                {{ config('services.theme.color') }}
            ;
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
            --bs-warning:
                {{ config('services.theme.color') }}
            ;
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
            background-color: var(--bs-warning) !important;
            color: #fff !important;
        }
    </style>
@endpush

@section('main_content')
    <div class="mybazar-login-section">
        <div class="mybazar-login-wrapper">
            <div class="login-wrapper">
                <div class="login-body w-100">
                    <div class="footer-logo w-100">
                        @php
                            $logoUrl = asset(get_option('general')['login_page_logo'] ?? 'assets/images/icons/logo.svg');
                        @endphp
                        <div class="logo-wrapper" style="width: 340px; max-width: 100%; height: 84px; margin: 0 auto;">
                            <div class="theme-logo-mask"
                                style="-webkit-mask-image: url('{{ $logoUrl }}'); mask-image: url('{{ $logoUrl }}');"></div>
                        </div>
                    </div>

                    @if(!empty($locked['name']) || !empty($locked['email']))
                        @php
                            $userRoleRaw = $locked['role'] ?? (Auth::user()->role ?? 'User');
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
                            $u = null;
                            if (isset($locked['id'])) {
                                $u = \App\Models\User::find($locked['id']);
                            }
                            $hasPinEnabled = $u && $u->pin_enabled;
                            $avatarUrl = url('upload/no_image.jpg');
                            $raw = $locked['photo'] ?? null;
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
                        <div class="d-flex flex-column align-items-center gap-2 mb-3">
                            <img class="lock-avatar" src="{{ $avatarUrl }}" alt="avatar" />
                            <div class="fw-semibold d-flex align-items-center gap-2">
                                {{ $locked['name'] ?? 'Locked' }}
                                <span class="badge {{ $roleBadgeClass }} text-uppercase"
                                    style="font-size: 0.55rem; line-height: 1;">{{ $roleLabel }}</span>
                            </div>
                            <div class="text-muted small">{{ $locked['email'] ?? '' }}</div>
                        </div>
                    @endif

                    @if($hasPinEnabled)
                        <div class="auth-method-toggle d-flex justify-content-center gap-2 mb-3">
                            <button type="button" class="btn btn-sm auth-method-btn {{ $hasPinEnabled ? 'active' : '' }}"
                                data-method="pin"
                                style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 4px 12px; font-size: 13px;">
                                {{ __('PIN') }}
                            </button>
                            <button type="button" class="btn btn-sm auth-method-btn {{ !$hasPinEnabled ? 'active' : '' }}"
                                data-method="password"
                                style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 4px 12px; font-size: 13px;">
                                {{ __('Password') }}
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('lock.unlock') }}" method="POST" class="my-4" id="unlockForm" novalidate>
                        @csrf
                        <input type="hidden" name="auth_method" id="authMethodInput"
                            value="{{ $hasPinEnabled ? 'pin' : 'password' }}">

                        <div id="passwordSection" class="auth-section {{ !$hasPinEnabled ? 'active' : '' }}"
                            style="{{ $hasPinEnabled ? 'display: none;' : '' }}">
                            <div class="input-group">
                                <span class="input-icon"><img src="{{ asset('assets/images/icons/lock.svg') }}"
                                        alt="img"></span>

                                <span class="hide-pass">
                                    <img src="{{ asset('assets/images/icons/show.svg') }}" alt="img">
                                    <img src="{{ asset('assets/images/icons/Hide.svg') }}" alt="img">
                                </span>

                                <input type="password" name="password" id="password"
                                    class="form-control w-100 password @error('password') is-invalid @enderror"
                                    placeholder="{{ __('Password') }}">
                            </div>
                            <div>
                                <span id="passwordError" class="invalid-feedback d-block mt-2"
                                    role="alert">@error('password'){{ $message }}@enderror</span>
                            </div>
                        </div>

                        @if($hasPinEnabled)
                            <div id="pinSection" class="auth-section {{ $hasPinEnabled ? 'active' : '' }}"
                                style="{{ !$hasPinEnabled ? 'display: none;' : 'display: block;' }}">
                                <div class="pin-input-container d-flex justify-content-center gap-2 mb-3">
                                    <input type="text" maxlength="1" class="form-control pin-input text-center" data-index="0"
                                        inputmode="numeric"
                                        style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                    <input type="text" maxlength="1" class="form-control pin-input text-center" data-index="1"
                                        inputmode="numeric"
                                        style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                    <input type="text" maxlength="1" class="form-control pin-input text-center" data-index="2"
                                        inputmode="numeric"
                                        style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                    <input type="text" maxlength="1" class="form-control pin-input text-center" data-index="3"
                                        inputmode="numeric"
                                        style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                </div>
                                <input type="hidden" name="pin" id="pinHiddenInput" value="">
                                <div>
                                    <span id="pinError" class="invalid-feedback d-block mt-2 text-center"
                                        role="alert">@error('pin'){{ $message }}@enderror</span>
                                </div>
                            </div>
                        @endif

                        <div class="form-group mb-0">
                            <div class="d-grid">
                                <button class="btn login-btn submit-btn" type="submit" id="unlockBtn">Unlock</button>
                            </div>
                        </div>
                    </form>

                    <div class="text-center text-muted">
                        <small>Not {{ $locked['name'] ?? 'you' }}? <a href="{{ route('lock.logout') }}" id="lockSignOutLink"
                                class="text-primary fw-medium">Sign in as a different user</a></small>
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
@endsection

@push('js')
    @include('components.session_heartbeat')
    <script src="{{ asset('assets/js/auth.js') }}"></script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pwd = document.getElementById('password');
            const authMethodInput = document.getElementById('authMethodInput');
            const authMethodBtns = document.querySelectorAll('.auth-method-btn');
            const passwordSection = document.getElementById('passwordSection');
            const pinSection = document.getElementById('pinSection');
            const pinInputs = document.querySelectorAll('.pin-input');
            const pinHiddenInput = document.getElementById('pinHiddenInput');
            const form = document.getElementById('unlockForm');
            const overlay = document.getElementById('lock-overlay');
            const unlockBtn = document.getElementById('unlockBtn');

            function showOverlay(msg) {
                if (overlay) {
                    overlay.querySelector('.msg').textContent = msg || 'Verifying…';
                    overlay.style.display = 'flex';
                }
            }
            function hideOverlay() {
                if (overlay) {
                    overlay.style.display = 'none';
                }
            }

            const initialMethod = authMethodInput ? authMethodInput.value : 'password';
            if (initialMethod === 'password' && pwd) {
                try { pwd.focus(); } catch (_) { }
            } else if (initialMethod === 'pin' && pinInputs[0]) {
                try { pinInputs[0].focus(); } catch (_) { }
            }

            if (pwd) {
                pwd.addEventListener('input', function () {
                    pwd.classList.remove('is-invalid');
                    const errEl = document.getElementById('passwordError');
                    if (errEl) errEl.textContent = '';
                });
            }

            // Auth toggle
            authMethodBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    const method = this.dataset.method;
                    authMethodBtns.forEach(b => {
                        b.classList.remove('active');
                        b.style.backgroundColor = 'transparent';
                        b.style.color = 'inherit';
                    });
                    this.classList.add('active');
                    this.style.backgroundColor = '{{ config('services.theme.color') }}';
                    this.style.color = '#fff';
                    authMethodInput.value = method;

                    // Clear inputs when toggling
                    if (pwd) {
                        pwd.value = '';
                        pwd.classList.remove('is-invalid');
                    }
                    if (pinInputs.length > 0) {
                        pinInputs.forEach(inp => {
                            inp.value = '';
                            inp.classList.remove('success', 'error');
                        });
                        if (pinHiddenInput) pinHiddenInput.value = '';
                    }
                    const errEls = [document.getElementById('passwordError'), document.getElementById('pinError')];
                    errEls.forEach(el => { if (el) el.textContent = ''; });

                    if (method === 'password') {
                        passwordSection.style.display = 'block';
                        if (pinSection) pinSection.style.display = 'none';
                        if (pwd) pwd.focus();
                    } else {
                        passwordSection.style.display = 'none';
                        if (pinSection) {
                            pinSection.style.display = 'block';
                            if (pinInputs[0]) pinInputs[0].focus();
                        }
                    }
                });
            });

            // Trigger click on active to set initial color
            const activeBtn = document.querySelector('.auth-method-btn.active');
            if (activeBtn) {
                activeBtn.style.backgroundColor = '{{ config('services.theme.color') }}';
                activeBtn.style.color = '#fff';
            }

            // PIN handling
            if (pinInputs.length > 0) {
                function updatePinHidden() {
                    let pin = '';
                    pinInputs.forEach(input => pin += input.value);
                    pinHiddenInput.value = pin;
                }

                pinInputs.forEach((input, index) => {
                    input.addEventListener('input', function () {
                        if (!/^\d*$/.test(this.value)) { this.value = ''; return; }

                        // Clear success/error states on input
                        pinInputs.forEach(inp => inp.classList.remove('success', 'error'));

                        updatePinHidden();
                        if (this.value.length === 1 && index < pinInputs.length - 1) {
                            pinInputs[index + 1].focus();
                        }
                        const err = document.getElementById('pinError');
                        if (err) err.textContent = '';

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
                        const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 4);
                        if (pasted) {
                            pinInputs.forEach((inp, i) => {
                                if (pasted[i]) inp.value = pasted[i];
                            });
                            updatePinHidden();
                            const next = Math.min(pasted.length, pinInputs.length - 1);
                            pinInputs[next].focus();
                            if (pasted.length === 4) {
                                setTimeout(() => form.dispatchEvent(new Event('submit')), 300);
                            }
                        }
                    });
                });
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const method = authMethodInput.value;

                    if (method === 'password') {
                        const errEl = document.getElementById('passwordError');
                        if (pwd && pwd.value.trim() === '') {
                            if (errEl) errEl.textContent = 'Please enter your password.';
                            pwd.classList.add('is-invalid');
                            try { pwd.focus(); } catch (_) { }
                            return;
                        }
                    } else {
                        const errEl = document.getElementById('pinError');
                        if (pinHiddenInput && pinHiddenInput.value.length < 4) {
                            if (errEl) errEl.textContent = 'Please enter complete 4-digit PIN.';
                            if (pinInputs[0]) pinInputs[0].focus();
                            return;
                        }
                    }

                    showOverlay('Verifying…');
                    const MIN_MS = 1500; const start = Date.now();
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
                                if (method === 'pin') {
                                    pinInputs.forEach(inp => inp.classList.add('success'));
                                }
                                const wait = Math.max(500, MIN_MS - (Date.now() - start));
                                setTimeout(() => {
                                    window.location.href = data.redirect || '/dashboard';
                                }, wait);
                            } else {
                                throw new Error(data.message || 'Unlock failed');
                            }
                        })
                        .catch(err => {
                            const msg = err && err.message ? err.message : 'Incorrect credentials. Please try again.';
                            if (method === 'pin') {
                                pinInputs.forEach(inp => inp.classList.add('error'));
                            }

                            // Keep overlay for the remaining MIN_MS to show the error state
                            const wait = Math.max(500, MIN_MS - (Date.now() - start));
                            setTimeout(() => {
                                hideOverlay();
                                if (method === 'password') {
                                    if (pwd) {
                                        pwd.classList.add('is-invalid');
                                        try { pwd.focus(); pwd.select(); } catch (e) { }
                                    }
                                    const errEl = document.getElementById('passwordError');
                                    if (errEl) { errEl.textContent = msg; }
                                } else {
                                    const errEl = document.getElementById('pinError');
                                    if (errEl) { errEl.textContent = msg; }
                                    // Clear PIN and remove error state immediately after overlay is gone
                                    pinInputs.forEach(inp => {
                                        inp.value = '';
                                        inp.classList.remove('error');
                                    });
                                    if (pinHiddenInput) pinHiddenInput.value = '';
                                    if (pinInputs[0]) pinInputs[0].focus();
                                }
                                if (window.toastr) { toastr.error(msg); }
                            }, wait);
                        });
                });
            }

            const signOutLink = document.getElementById('lockSignOutLink');
            if (signOutLink) {
                signOutLink.addEventListener('click', function (e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    const MIN_OVERLAY_MS = 2000;
                    showOverlay('Signing out…');
                    if (window.toastr) {
                        const prev = Object.assign({}, window.toastr.options || {});
                        try {
                            window.toastr.options = Object.assign({}, prev, { positionClass: 'toast-bottom-right', timeOut: 1500, extendedTimeOut: 200, progressBar: true, closeButton: true });
                            window.toastr.info('Signing out…');
                        } finally { window.toastr.options = prev; }
                    }
                    try { localStorage.setItem('toast-next', JSON.stringify({ type: 'success', message: 'You have been logged out successfully.', timeout: 3000 })); } catch (e) { }
                    setTimeout(function () { window.location.href = href; }, MIN_OVERLAY_MS);
                });
            }
        });
    </script>
@endpush