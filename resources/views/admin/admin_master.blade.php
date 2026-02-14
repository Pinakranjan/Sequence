<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A fully featured admin theme which can be used to build CRM, CMS, etc." />
    <meta name="author" content="Zoyothemes" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.ico') }}">

    <!-- Quill css -->
    <link href="{{ asset('backend/assets/libs/quill/quill.core.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/libs/quill/quill.snow.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/libs/quill/quill.bubble.css') }}" rel="stylesheet" type="text/css" />

    <!-- Datatables css -->
    <link href="{{ asset('backend/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}"
        rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css') }}"
        rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/libs/datatables.net-keytable-bs5/css/keyTable.bootstrap5.min.css') }}"
        rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}"
        rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css') }}"
        rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />
    <link
        href="{{ asset('backend/assets/css/custom.css') }}?v={{ filemtime(public_path('backend/assets/css/custom.css')) }}"
        rel="stylesheet" type="text/css" />
    <!-- Dal-icious Sidebar Styles -->
    <link
        href="{{ asset('backend/assets/css/sidebar-dal-icious.css') }}?v={{ filemtime(public_path('backend/assets/css/sidebar-dal-icious.css')) }}"
        rel="stylesheet" type="text/css" />

    <!-- Icons -->
    <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Font Awesome (for classes like `fas fa-user`) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Flatpickr Timepicker css -->
    <link href="{{ asset('backend/assets/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Material Icons (Outlined) for DataTable toolbar icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css">
    <style>
        /* Make toastr width dynamic based on content */
        #toast-container>div {
            width: fit-content !important;
            min-width: 180px !important;
            max-width: 500px !important;
            padding-right: 40px !important;
            /* space for close button */
        }

        #toast-container>div.toast {
            width: fit-content !important;
        }

        /* Ensure progress bar is visible at bottom */
        .toast-progress {
            position: absolute !important;
            bottom: 0 !important;
            left: 0 !important;
            height: 3px !important;
            opacity: 0.8 !important;
            pointer-events: none;
        }
    </style>

    <!-- Syncfusion EJ2 CSS (global) -->
    <link rel="stylesheet"
        href="https://cdn.syncfusion.com/ej2/{{ config('services.syncfusion.version', '31.1.0') }}/bootstrap5.css" />
    <!-- material.css -->
    <!-- <link href="https://cdn.syncfusion.com/ej2/25.1.35/material.css" rel="stylesheet"/> -->

    @php
        $useLegacyErpAdminAssets = false;
        $useLegacyErpFormJs = false;
    @endphp


    {{-- Page-specific styles (e.g., component overrides) --}}
    @stack('page-styles')
    @stack('css')


    <!-- Footer at page end: flows naturally after content, no fixed positioning -->
    <style>
        /* Footer flows naturally at the end of content - not fixed */
        .content-page footer,
        .content-page .footer {
            position: relative;
            background: transparent;
            padding: 15px 0;
            margin-top: auto;
        }

        /* Ensure content-page uses flexbox to push footer to bottom on short pages */
        .content-page {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 70px);
            /* 70px is header height */
        }

        .content-page .content,
        .content-page>.container-xxl,
        .content-page>.container-fluid {
            flex: 1 0 auto;
        }
    </style>
</head>

<!-- body start -->

@php
    $isSlimInterface = false;
@endphp

<body data-menu-color="light" data-sidebar="default" class="{{ request()->routeIs('home') ? 'home-bg' : '' }}">

    <!-- Begin page -->
    <div id="app-layout">
        <!-- Topbar Start -->
        @include('admin.body.header')
        <!-- end Topbar -->

        {{-- Left Sidebar Start --}}
        @unless($isSlimInterface)
            @include('admin.body.sidebar')
        @endunless
        {{-- Left Sidebar End --}}

        {{-- ============================================================== --}}
        {{-- Start Page Content here --}}
        {{-- ============================================================== --}}

        <div class="content-page">

            @hasSection('admin')
                @yield('admin')
            @else
                @yield('main_content')
            @endif
            @stack('modal')
            <!-- content -->

            <!-- Footer Start -->
            @include('admin.body.footer')
            <!-- end Footer -->
        </div>
        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

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

    <!-- Apexcharts JS -->
    <script src="{{ asset('backend/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- for basic area chart -->
    <script src="https://apexcharts.com/samples/assets/stock-prices.js"></script>

    <!-- App js-->
    <script src="{{ asset('backend/assets/js/app.js') }}?v={{ time() }}"></script>

    <!-- Datatables js -->
    <script src="{{ asset('backend/assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>

    <!-- dataTables.bootstrap5 -->
    <script src="{{ asset('backend/assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <!-- PDFMake for PDF export (required by buttons.html5) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <!-- DataTables Buttons plugins -->
    <script src="{{ asset('backend/assets/libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <!-- JSZip for Excel export (required by buttons.html5) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <!-- DataTables Responsive -->
    <script src="{{ asset('backend/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script
        src="{{ asset('backend/assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>

    <!-- Datatable Demo App Js -->
    {{--
    <script src="{{ asset('backend/assets/js/pages/datatable.init.js') }}"></script>--}}
    <!-- Common DataTables initializer -->
    <script src="{{ asset('backend/assets/js/pages/datatable.common.js') }}?v={{ time() }}"></script>

    <!-- Flatpickr Timepicker Plugin js -->
    <script src="{{ asset('backend/assets/libs/flatpickr/flatpickr.min.js') }}"></script>

    <!-- Quill Editor Js -->
    <script src="{{ asset('backend/assets/libs/quill/quill.core.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/quill/quill.min.js') }}"></script>

    <!-- Quill Demo Js -->
    <script src="{{ asset('backend/assets/js/pages/quilljs.init.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="{{ asset('backend/assets/js/code.js') }}"></script>

    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        // Toastr global default position (authenticated admin pages)
        try {
            if (window.toastr) {
                window.toastr.options = Object.assign({}, window.toastr.options || {}, {
                    positionClass: 'toast-bottom-right'
                });
            }
        } catch (e) { }
    </script>

    <!-- Syncfusion EJ2 JS (global) -->
    <script
        src="https://cdn.syncfusion.com/ej2/{{ config('services.syncfusion.version', '31.1.35') }}/dist/ej2.min.js"></script>

    <script>
        // Create a simple readiness gate and register license when EJ2 becomes available
        (function () {
            const licenseKey = @json(config('services.syncfusion.license'));
            // console.log('licenseKey:', licenseKey ? 'Provided' : 'Not provided');
            window.waitForEJ2 = new Promise(function (resolve, reject) {
                const start = Date.now(), maxWait = 15000;
                (function check() {
                    if (window.ej && ej.base) {
                        try {
                            if (licenseKey && typeof ej.base.registerLicense === 'function') {
                                ej.base.registerLicense(licenseKey);
                                // console.info('Syncfusion license registered.');
                            } else {
                                console.warn('Syncfusion license key missing or registerLicense not available.');
                            }
                        } catch (e) { console.error('Error registering Syncfusion license:', e); }
                        resolve(ej);
                        return;
                    }
                    if (Date.now() - start > maxWait) {
                        reject(new Error('EJ2 failed to load.'));
                        return;
                    }
                    setTimeout(check, 50);
                })();
            });
        })();
    </script>

    @stack('page-scripts')
    @stack('js')

    {{-- Legacy ERP admin JS for Front CMS pages (AJAX form handling + toastr notifications) --}}
    {{-- Excludes website-settings (Manage Pages) which has its own custom AJAX handler --}}
    @if ($useLegacyErpFormJs)
        <script src="{{ asset('assets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/custom/notification.js') }}"></script>
        <script src="{{ asset('assets/plugins/validation-setup/validation-setup.js') }}"></script>
        <script src="{{ asset('assets/plugins/custom/form.js') }}?v={{ time() }}"></script>
    @endif


    <script>
        // Re-assert after page scripts
        try {
            if (window.toastr) {
                try { var __tc3 = document.getElementById('toast-container'); if (__tc3 && __tc3.parentNode) __tc3.parentNode.removeChild(__tc3); } catch (_) { }
                window.toastr.options = Object.assign({}, window.toastr.options || {}, {
                    positionClass: 'toast-bottom-right'
                });
            }
        } catch (e) { }
    </script>

    <script>
        @if(Session::has('message'))

            // Configure toastr - enable progress bar and set timeouts
            try { var __tc4 = document.getElementById('toast-container'); if (__tc4 && __tc4.parentNode) __tc4.parentNode.removeChild(__tc4); } catch (_) { }
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": {!! json_encode(Session::get('positionClass', 'toast-bottom-right')) !!},
                "preventDuplicates": false,
                "newestOnTop": true,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": {{ Session::get('timeout', 5000) }},          // main timeout in ms (defaults to 5000)
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            var type = "{{ Session::get('alert-type', 'info') }}"
            switch (type) {
                case 'info':
                    toastr.info(" {{ Session::get('message') }} ");
                    break;

                case 'success':
                    toastr.success(" {{ Session::get('message') }} ");
                    break;

                case 'warning':
                    toastr.warning(" {{ Session::get('message') }} ");
                    break;

                case 'error':
                    toastr.error(" {{ Session::get('message') }} ");
                    break;
            }
        @endif
    </script>

    {{-- Global session heartbeat for all authenticated admin pages --}}
    @include('components.session_heartbeat')
</body>

</html>