@extends('layouts.auth.app')

@section('title')
    {{ __('Forgot Password') }}
@endsection

@section('main_content')
    <div class="footer position-relative">
        <div class="mybazar-login-section ">
            <div class="mybazar-login-wrapper ">
                <div class="login-wrapper">
                    <div class="login-body w-100">
                        <div class="footer-logo w-100">
                            @php
                                $logoUrl = asset(get_option('general')['login_page_logo'] ?? 'assets/images/icons/logo.svg');
                            @endphp
                            <div class="logo-wrapper" style="width: 340px; max-width: 100%; height: 84px; margin: 0 auto;">
                                <div class="theme-logo-mask"
                                    style="-webkit-mask-image: url('{{ $logoUrl }}'); mask-image: url('{{ $logoUrl }}');">
                                </div>
                            </div>
                        </div>

                        <h2 class="login-title">{{ __('Forgot Password') }}</h2>
                        <h6 class="login-para">{{ __('Enter your email to continue') }}</h6>

                        <form method="POST" action="{{ route('password.code.email') }}">
                            @csrf
                            <div class="input-group">
                                <span class="input-icon"><img src="{{ asset('assets/images/icons/email.svg') }}"
                                        alt="img"></span>
                                <input type="email" name="email" class="form-control w-100 dynamictext"
                                    value="{{ old('email') }}" placeholder="{{ __('Enter your Email') }}">
                            </div>

                            <button type="submit" class="btn login-btn submit-btn">{{ __('Continue') }}</button>
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