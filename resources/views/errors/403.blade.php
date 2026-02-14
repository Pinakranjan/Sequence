<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Access denied" />
    <meta name="author" content="Weighguru" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Error 403 | Weighguru</title>

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
                            <!-- Use the same image style as 404 per request -->
                            <img src="{{ asset('backend/assets/images/svg/403_error.svg') }}" class="img-fluid"
                                alt="forbidden">
                        </div>

                        <div class="text-center">
                            <h3 class="mt-5 fw-semibold text-black text-capitalize">Access Denied</h3>
                            <p class="text-muted">You are not authorized to access this page. <br> If you believe this
                                is a mistake, please contact your administrator.</p>
                        </div>

                        <a id="backHomeBtn" class="btn btn-primary mt-1 me-1"
                            style="background-color: #fd7e14; border-color: #fd7e14;" href="{{ url('/') }}">Back to
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
    <script>
        (function () {
            var fallback = "{{ url('/') }}";
            var checkUrl = "{{ route('auth.check') }}";
            var btn = document.getElementById('backHomeBtn');
            if (btn) {
                btn.addEventListener('click', function (e) {
                    // Allow normal navigation if JS disabled; otherwise intercept and decide destination
                    e.preventDefault();
                    fetch(checkUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (res) { return res.json().catch(function () { return null; }); })
                        .then(function (data) {
                            var target = (data && data.redirect) ? data.redirect : fallback;
                            window.location.href = target;
                        })
                        .catch(function () {
                            window.location.href = fallback;
                        });
                });
            }
        })();
    </script>

</body>

</html>