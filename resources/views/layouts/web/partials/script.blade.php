<script src="{{ asset('assets/web/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/web/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/web/js/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('assets/web/js/lity.min.js') }}"></script>
<script src="{{ asset('assets/web/js/slick.min.js') }}"></script>
<script src="{{ asset('assets/web/js/type.js') }}"></script>
<script src="{{ asset('assets/web/js/aos.js') }}"></script>

<script src="{{ asset('assets/plugins/custom/notification.js') }}"></script>
<script src="{{ asset('assets/plugins/validation-setup/validation-setup.js') }}"></script>
<script src="{{ asset('assets/js/toastr.min.js') }}"></script>
<script src="{{ asset('assets/plugins/jquery-confirm/jquery-confirm.min.js')}}"></script>
<script src="{{ asset('assets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/form.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/custom-ajax.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/web/js/custom.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/auth.js') }}?v={{ time() }}"></script>

<script>
    // Toastr global default position (web pages)
    try {
        if (window.toastr) {
            window.toastr.options = Object.assign({}, window.toastr.options || {}, {
                positionClass: 'toast-bottom-right'
            });
        }
    } catch (e) {}
</script>

@stack('js')

@if(Session::has('message'))
    <script>
        toastr.success( "{{ Session::get('message') }}");
    </script>
@endif

@if(Session::has('error'))
    <script>
        toastr.error( "{{ Session::get('error') }}");
    </script>
@endif

@if(Session::has('warning'))
    <script>
        toastr.warning( "{{ Session::get('warning') }}");
    </script>
@endif
