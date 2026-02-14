@extends('layouts.auth.app')

@section('title')
    {{ __('Set New Password') }}
@endsection

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

                        <h2 class="login-title">{{ __('Set Up New Password') }}</h2>
                        <h6 class="login-para">{{ __('Create a new password to continue') }}</h6>

                        <form method="POST" action="{{ route('password.code.password') }}" novalidate>
                            @csrf
                            <input type="hidden" name="email"
                                value="{{ session('pending_password_reset_email') ?? request('email') }}">

                            <div class="input-group">
                                <span class="input-icon"><img src="{{ asset('assets/images/icons/lock.svg') }}"
                                        alt="img"></span>
                                <span class="hide-pass">
                                    <img src="{{ asset('assets/images/icons/show.svg') }}" alt="img">
                                    <img src="{{ asset('assets/images/icons/Hide.svg') }}" alt="img">
                                </span>
                                <input class="form-control w-100 @error('password') is-invalid @enderror" type="password"
                                    id="password" name="password" required placeholder="{{ __('New Password') }}">
                            </div>

                            <div class="input-group">
                                <span class="input-icon"><img src="{{ asset('assets/images/icons/lock.svg') }}"
                                        alt="img"></span>
                                <span class="hide-pass">
                                    <img src="{{ asset('assets/images/icons/show.svg') }}" alt="img">
                                    <img src="{{ asset('assets/images/icons/Hide.svg') }}" alt="img">
                                </span>
                                <input class="form-control w-100 @error('password_confirmation') is-invalid @enderror"
                                    type="password" id="password_confirmation" name="password_confirmation" required
                                    placeholder="{{ __('Confirm Password') }}">
                            </div>

                            <button type="submit" class="btn login-btn submit-btn">{{ __('Continue') }}</button>
                        </form>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a class="backhome" href="{{ url('/') }}">{{ __('Back to Home') }}</a>
                        <a class="backhome" href="{{ route('login') }}">{{ __('Back to Login') }}</a>
                    </div>
                </div>

                <div class="login-img">
                    <img src="{{ asset(get_option('general')['login_page_img'] ?? 'assets/images/login/login-avatar.png') }}"
                        alt="">
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" data-model="Reset" id="auth">
@endsection

@push('js')
    <script src="{{ asset('assets/js/auth.js') }}"></script>

    @if (session('status'))
        <script>
            try { Notify('success', null, @json(session('status'))); } catch (e) { }
        </script>
    @endif
    @if ($errors->any())
        <script>
            try { Notify('error', null, @json($errors->first())); } catch (e) { }
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form[action*="password/forgot/password"], form[action*="password.code.password"]');
            const pwd = document.getElementById('password');
            const pwd2 = document.getElementById('password_confirmation');

            const firstInvalid = document.querySelector('.is-invalid');
            if (firstInvalid instanceof HTMLElement) {
                try { firstInvalid.focus({ preventScroll: true }); if (firstInvalid.select) firstInvalid.select(); } catch (_) { }
            } else if (pwd instanceof HTMLInputElement) {
                pwd.focus();
                try { pwd.select(); } catch (_) { }
            }

            function setError(input, msg) {
                if (!input) return;
                input.classList.add('is-invalid');
                try { input.focus({ preventScroll: true }); if (input.select) input.select(); } catch (_) { }
                try { Notify('error', null, msg); } catch (_) { }
            }

            function clearError(input) {
                if (!input) return;
                input.classList.remove('is-invalid');
            }

            if (pwd) pwd.addEventListener('input', function () { clearError(pwd); });
            if (pwd2) pwd2.addEventListener('input', function () { clearError(pwd2); });

            if (form) {
                form.addEventListener('submit', function (e) {
                    if (!pwd || !pwd2) return;
                    let hasError = false;
                    if (pwd.value.trim() === '') { setError(pwd, 'Please enter your new password.'); hasError = true; }
                    if (pwd2.value.trim() === '') { setError(pwd2, 'Please confirm your new password.'); hasError = true; }
                    if (hasError) e.preventDefault();
                });
            }
        });
    </script>
@endpush