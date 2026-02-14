@php
    $configuredFavicon = get_option('general')['favicon'] ?? null;
    if ($configuredFavicon && file_exists(public_path($configuredFavicon))) {
        $faviconUrl = asset($configuredFavicon) . '?v=' . filemtime(public_path($configuredFavicon));
    } else {
        $faviconUrl = url('/favicon.svg');
    }
@endphp

<link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ $faviconUrl }}">
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

<style>
    @php
        $themeColor = config('services.theme.color');
        // Convert hex to rgb
        if (preg_match('/^#([a-f0-9]{6})$/i', $themeColor)) {
            list($r, $g, $b) = sscanf($themeColor, "#%02x%02x%02x");
            $themeColorRgb = "$r, $g, $b";
        } else {
            $themeColorRgb = "255, 122, 42"; // Fallback to orange if invalid hex
        }
    @endphp
    :root {
        --theme-color:
            {{ $themeColor }}
        ;
        --theme-color-rgb:
            {{ $themeColorRgb }}
        ;
        --clr-primary:
            {{ $themeColor }}
        ;
        --bs-primary:
            {{ $themeColor }}
        ;
    }

    .login-btn,
    .submit-btn,
    .btn-primary {
        background-color: var(--theme-color) !important;
        border-color: var(--theme-color) !important;
        color: #fff !important;
    }

    .login-btn:hover,
    .submit-btn:hover,
    .btn-primary:hover {
        background-color: var(--theme-color) !important;
        border-color: var(--theme-color) !important;
        opacity: 0.9;
    }

    .form-control:focus {
        border-color: var(--theme-color) !important;
        box-shadow: 0 0 0 0.25rem
            {{ config('services.theme.color') }}
            26 !important;
        /* 15% opacity */
    }

    a {
        color: var(--theme-color);
    }

    .theme-logo-mask {
        background-color: var(--theme-color);
        -webkit-mask-size: contain;
        mask-size: contain;
        -webkit-mask-repeat: no-repeat;
        mask-repeat: no-repeat;
        -webkit-mask-position: center;
        mask-position: center;
        width: 100%;
        height: 100%;
        display: block;
    }

    /* Fix for dim labels in Auth Pages */
    .mybazar-login-section label,
    .mybazar-login-section .form-label,
    .mybazar-login-section .form-check-label,
    .mybazar-login-section p,
    .mybazar-login-section .login-para {
        color: #000 !important;
        opacity: 1 !important;
    }

    .mybazar-login-section .text-muted {
        color: #6c757d !important;
        /* Keep legitimate muted text grey but readable */
    }

    .backhome {
        color: #6c757d !important;
        /* Default grey */
        font-weight: 500;
        text-decoration: none;
    }

    .backhome:hover {
        color: var(--theme-color) !important;
    }

    .block-color {
        color: #000 !important;
    }

    .modal-backdrop {
        background-color: #000;
        opacity: 0.5;
    }

    .text-primary {
        color: var(--theme-color) !important;
    }

    .bg-primary {
        background-color: var(--theme-color) !important;
    }
</style>