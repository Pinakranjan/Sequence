@extends('layouts.auth.app')

@section('title')
    {{ __('Reset Password') }}
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
                        <h6 class="login-para">{{ __('Reset your password and log in your account') }}</h6>

                        <form method="POST" action="{{ route('password.store') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $request->route('token') }}">

                            <div class="input-group">
                                <span class="input-icon"><img src="{{ asset('assets/images/icons/email.svg') }}"
                                        alt="img"></span>
                                <input type="email" id="email" name="email" class="form-control w-100 dynamictext"
                                    value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                                    placeholder="{{ __('Enter your Email') }}">
                            </div>

                            <div class="input-group">
                                <span class="input-icon"><img src="{{ asset('assets/images/icons/lock.svg') }}"
                                        alt="img"></span>
                                <span class="hide-pass">
                                    <img src="{{ asset('assets/images/icons/show.svg') }}" alt="img">
                                    <img src="{{ asset('assets/images/icons/Hide.svg') }}" alt="img">
                                </span>
                                <input type="password" id="password" name="password" class="form-control w-100" required
                                    autocomplete="new-password" placeholder="{{ __('New Password') }}">
                            </div>

                            <div class="input-group">
                                <span class="input-icon"><img src="{{ asset('assets/images/icons/lock.svg') }}"
                                        alt="img"></span>
                                <span class="hide-pass">
                                    <img src="{{ asset('assets/images/icons/show.svg') }}" alt="img">
                                    <img src="{{ asset('assets/images/icons/Hide.svg') }}" alt="img">
                                </span>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="form-control w-100" required autocomplete="new-password"
                                    placeholder="{{ __('Confirm Password') }}">
                            </div>

                            <button type="submit" class="btn login-btn submit-btn">{{ __('Continue') }}</button>
                        </form>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a class="backhome" href="{{ route('login') }}">{{ __('Back to Login') }}</a>
                        <a class="backhome" href="{{ url('/') }}">{{ __('Back to Home') }}</a>
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
@endpush