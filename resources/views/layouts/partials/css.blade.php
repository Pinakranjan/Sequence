@php
    $configuredFavicon = get_option('general')['favicon'] ?? null;
    $faviconPath = ($configuredFavicon && file_exists(public_path($configuredFavicon)))
        ? $configuredFavicon
        : 'assets/images/logo/favicon.svg';
    $faviconVersion = file_exists(public_path($faviconPath)) ? filemtime(public_path($faviconPath)) : time();
@endphp

<link rel="icon" type="image/x-icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}">
<!-- Bootstrap -->
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
<!-- Fontawesome -->
<link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome/css/fontawesome-all.min.css') }}">
{{-- jquery-confirm --}}
<link rel="stylesheet" href="{{ asset('assets/plugins/jquery-confirm/jquery-confirm.min.css') }}">

<!-- Lily -->
<link rel="stylesheet" href="{{ asset('assets/css/lity.css') }}">
<!-- Style -->
<link rel="stylesheet" href="{{ asset('assets/css/admin-style.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ time() }}">
<!-- Toaster -->
<link rel="stylesheet" href="{{ asset('assets/css/toastr.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/choices.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/flatpickr.min.css') }}">



@stack('css')

@if (app()->getLocale() == 'ar' || app()->getLocale() == 'arbh')
    <link rel="stylesheet" href="{{ asset('assets/css/arabic.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.rtl.min.css') }}?v={{ time() }}">
@endif