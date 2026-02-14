<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Error 503 | Weighguru</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A fully featured admin theme which can be used to build CRM, CMS, etc." />
    <meta name="author" content="Zoyothemes" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.ico') }}">

    <!-- App css -->
    <link href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Icons -->
    <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

</head>

<body class="bg-white">
    <!-- Begin page -->
    <div class="maintenance-pages">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-12">

                    <div class="text-center">
                        <div class="mb-4 text-center">
                            <a href="/" class="auth-logo">
                                <img src="{{ asset('/upload/25/08/1754301621-844.svg') }}" alt="logo-dark"
                                    class="mx-auto" height="48" />
                            </a>
                        </div>

                        <div class="maintenance-img">
                            <img src="{{ asset('backend/assets/images/svg/503-error.svg') }}" class="img-fluid"
                                alt="503-error">
                        </div>

                        <div class="text-center">
                            <h3 class="mt-4 fw-semibold text-black text-capitalize">Service unavailable</h3>
                            <p class="text-muted">Temporary service outage. Please try again later.</p>
                        </div>

                        @php
                            $homeUrl = '/';
                            if (auth()->check()) {
                                $homeUrl = route('dashboard');
                            }
                        @endphp
                        <a class="btn btn-primary mt-3 me-1" style="background-color: #fd7e14; border-color: #fd7e14;"
                            href="{{ $homeUrl }}" id="backToHomeBtn">Back to Home</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- END wrapper -->

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
    <script>
        (function () {
            try {
                // Try to recover the last successful URL from sessionStorage
                var btn = document.getElementById('backToHomeBtn');
                if (!btn) return;

                var lastUrl = null;
                try {
                    lastUrl = sessionStorage.getItem('wg-last-ok-url') || null;
                } catch (_) { }

                if (lastUrl && typeof lastUrl === 'string') {
                    btn.setAttribute('href', lastUrl);
                }
            } catch (_) { }
        })();
    </script>

</body>

</html>