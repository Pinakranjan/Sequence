<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Page expired" />
    <meta name="author" content="Weighguru" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Error 419 | Weighguru</title>

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.ico') }}">

    <!-- App css -->
    <link href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Icons -->
    <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

</head>

<body class="bg-white" data-menu-color="light" data-sidebar="default">
    <div class="maintenance-pages">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="text-center">
                        <div class="mb-5 text-center">
                            <a href="{{ url('/') }}" class="auth-logo">
                                <img src="{{ asset('/upload/25/08/1754301621-844.svg') }}" alt="logo-dark"
                                    class="mx-auto" height="48" />
                            </a>
                        </div>

                        <div class="maintenance-img">
                            <img src="{{ asset('backend/assets/images/svg/offline.svg') }}" class="img-fluid"
                                alt="page-expired">
                        </div>

                        <div class="text-center">
                            <h3 class="mt-5 fw-semibold text-black text-capitalize">419 - Page Expired</h3>
                            <p class="text-muted">
                                Your session may have expired, or the form token is no longer valid.
                                <br>Use the buttons below to continue.
                            </p>
                        </div>

                        @php
                            $homeUrl = '/';
                            try {
                                if (Route::has('home')) {
                                    $homeUrl = route('home');
                                }
                            } catch (\Throwable $e) {
                                $homeUrl = '/';
                            }

                            if (auth()->check()) {
                                try {
                                    if (Route::has('dashboard')) {
                                        $homeUrl = route('dashboard');
                                    }
                                } catch (\Throwable $e) {
                                    // keep existing $homeUrl
                                }
                            }

                            $loginUrl = null;
                            try {
                                if (Route::has('login')) {
                                    $loginUrl = route('login');
                                }
                            } catch (\Throwable $e) {
                                $loginUrl = null;
                            }
                        @endphp

                        <div class="d-flex justify-content-center gap-2 flex-wrap mt-2">
                            <a class="btn btn-primary" style="background-color: #fd7e14; border-color: #fd7e14;"
                                href="{{ $homeUrl }}">Back to Home</a>

                            @if (!auth()->check() && $loginUrl)
                                <a class="btn btn-outline-secondary" href="{{ $loginUrl }}">Login</a>
                            @endif

                            <a class="btn btn-outline-secondary" href="{{ url('/') }}">Site Root</a>
                        </div>

                        <div class="text-muted mt-4" style="max-width: 720px; margin: 0 auto;">
                            <small>
                                If you were submitting a form, go back and submit again. On iPad Safari, you may need to
                                close the tab and reopen the site to refresh cookies.
                            </small>
                        </div>

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

</body>

</html>