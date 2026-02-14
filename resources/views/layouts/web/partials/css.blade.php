@php
	$configuredFavicon = get_option('general')['favicon'] ?? null;
	$faviconPath = ($configuredFavicon && file_exists(public_path($configuredFavicon)))
		? $configuredFavicon
		: 'favicon.svg';
	$faviconVersion = file_exists(public_path($faviconPath)) ? filemtime(public_path($faviconPath)) : time();
@endphp

<link rel="icon" type="image/x-icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}">
<link rel="stylesheet" href="{{ asset('assets/web/css/bootstrap.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/swiper-bundle.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/fonts/fontawesome/css/all.min.css') }}" />
<!-- Slick Slider -->
<link rel="stylesheet" href="{{ asset('assets/web/css/slick.css') }}" />
{{-- jquery-confirm --}}
<link rel="stylesheet" href="{{ asset('assets/plugins/jquery-confirm/jquery-confirm.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/web/css/slick-theme.css') }}" />
<!-- Custom Css -->
<link rel="stylesheet" href="{{ asset('assets/web/css/styles.css') }}?v={{ time() }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/responsive.css') }}?v={{ time() }}" />
<link rel="stylesheet" href="{{ asset('assets/css/payments.css') }}?v={{ time() }}" />

<!-- Toaster -->
<link rel="stylesheet" href="{{ asset('assets/css/toastr.min.css') }}">

{{-- aos animation --}}
<link rel="stylesheet" href="{{ asset('assets/web/css/aos.css') }}" />


@if (app()->getLocale() == 'ar')
	<link rel="stylesheet" href="{{ asset('assets/web/css/arabic.css') }}?v={{ time() }}">
	<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.rtl.min.css') }}">
@endif

@stack('css')