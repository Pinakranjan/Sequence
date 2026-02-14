<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/theme.js') }}"></script>
{{-- jquery confirm --}}
<script src="{{asset('assets/plugins/jquery-confirm/jquery-confirm.min.js')}}"></script>
{{-- jquery validation --}}
<script src="{{asset('assets/plugins/jquery-validation/jquery.validate.min.js')}}"></script>
{{-- Custom --}}
<script src="{{ asset('assets/plugins/validation-setup/validation-setup.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/notification.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/form.js') }}?v={{ time() }}"></script>
{{-- Status --}}
<script src="{{ asset('assets/js/custom-ajax.js') }}?v={{ time() }}"></script>
{{-- Toaster --}}
<script src="{{ asset('assets/js/toastr.min.js') }}"></script>
<script src="{{ asset('assets/js/custom/custom.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/choices.min.js') }}"></script>

<script>
    // Toastr global default position (all pages)
    try {
        if (window.toastr) {
            try { var __tc = document.getElementById('toast-container'); if (__tc && __tc.parentNode) __tc.parentNode.removeChild(__tc); } catch(_) {}
            window.toastr.options = Object.assign({}, window.toastr.options || {}, {
                positionClass: 'toast-bottom-right'
            });
        }
    } catch (e) {}
</script>


@stack('js')

<script>
    // Re-assert after page scripts (some pages may override toastr.options)
    try {
        if (window.toastr) {
            try { var __tc2 = document.getElementById('toast-container'); if (__tc2 && __tc2.parentNode) __tc2.parentNode.removeChild(__tc2); } catch(_) {}
            window.toastr.options = Object.assign({}, window.toastr.options || {}, {
                positionClass: 'toast-bottom-right'
            });
        }
    } catch (e) {}
</script>

@stack('modal-view')

{{-- Toaster Message --}}
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

@if($errors->any())
<script>
    toastr.warning('Error some occurs!');
</script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const allSelects = document.querySelectorAll('.choices-select');

        allSelects.forEach(function (selectEl) {
            new Choices(selectEl, {
                searchEnabled: false,
                itemSelectText: '',
                shouldSort: false
            });
        });
    });
</script>
