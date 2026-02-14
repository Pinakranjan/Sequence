<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Page not found" />
    <meta name="author" content="Weighguru" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Error 404 | Weighguru</title>

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.ico') }}">

    <!-- App css -->
    <link href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Icons -->
    <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        :root {
            --theme-color:
                {{ config('services.theme.color') }}
            ;
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
    </style>
</head>

<body class="bg-white" data-menu-color="light" data-sidebar="default">
    <div class="maintenance-pages">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="text-center">
                        <div class="mb-5 text-center">
                            <a href="{{ route('home') }}" class="auth-logo">
                                @php
                                    $logoUrl = asset(get_option('general')['login_page_logo'] ?? 'assets/images/icons/logo.svg');
                                @endphp
                                <div class="logo-wrapper"
                                    style="width: 340px; max-width: 100%; height: 84px; margin: 0 auto;">
                                    <div class="theme-logo-mask"
                                        style="-webkit-mask-image: url('{{ $logoUrl }}'); mask-image: url('{{ $logoUrl }}');">
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="maintenance-img">
                            <img src="{{ asset('backend/assets/images/svg/404-error.svg') }}" class="img-fluid"
                                alt="coming-soon">
                        </div>

                        <div class="text-center">
                            <h3 class="mt-5 fw-semibold text-black text-capitalize">Oops!, Page Not Found</h3>
                            <p class="text-muted">The page you are trying to access does not exist or has been moved.
                                <br> Try going back to our homepage.
                            </p>
                        </div>

                        @php
                            $homeUrl = route('home');
                            if (auth()->check()) {
                                $homeUrl = route('dashboard');
                            }
                        @endphp
                        <a id="backHomeBtn" class="btn btn-primary mt-1 me-1"
                            style="background-color: {{ config('services.theme.color') }}; border-color: {{ config('services.theme.color') }};"
                            href="{{ $homeUrl }}">Back to
                            Home</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Vendor -->
    <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/waypoints/lib/jquery.waypoints.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/jquery.counterup/jquery.counterup.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/feather-icons/feather.min.js') }}"></script>

    <!-- App js-->
    <script src="{{ asset('backend/assets/js/app.js') }}"></script>
    <!-- Back to Home button links directly to the site root -->

</body>

</html>