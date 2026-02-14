<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/toastr.min.js') }}"></script>

<script>
    // Toastr global default position (auth pages) — remove existing container first
    try {
        if (window.toastr) {
            try { var __tc = document.getElementById('toast-container'); if (__tc && __tc.parentNode) __tc.parentNode.removeChild(__tc); } catch(_) {}
            window.toastr.options = Object.assign({}, window.toastr.options || {}, {
                positionClass: 'toast-bottom-right'
            });
        }
    } catch (e) {}
</script>

{{-- jquery confirm --}}
<script src="{{ asset('assets/plugins/jquery-confirm/jquery-confirm.min.js') }}"></script>
{{-- jquery validation --}}
<script src="{{ asset('assets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
{{-- Custom --}}
<script src="{{ asset('assets/plugins/validation-setup/validation-setup.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/notification.js') }}?v={{ filemtime(public_path('assets/plugins/custom/notification.js')) }}"></script>
<script src="{{ asset('assets/plugins/custom/form.js') }}?v={{ time() }}"></script>
@stack('js')

@if (Session::has('success'))
    <script>
        Notify('success', null, @json(Session::get('success')));
    </script>
@endif
@if (Session::has('error'))
    <script>
        Notify('error', null, @json(Session::get('error')));
    </script>
@endif

<script>
    // Focus the first invalid input on auth pages (global helper)
    document.addEventListener('DOMContentLoaded', function () {
        try {
            var firstInvalid = document.querySelector('.mybazar-login-section .is-invalid, .mybazar-login-section .invalid, .mybazar-login-section input.error');
            if (firstInvalid instanceof HTMLElement) {
                try { firstInvalid.focus({ preventScroll: true }); if (firstInvalid.select) firstInvalid.select(); } catch (e) {}
                return;
            }

            // no invalid field — focus the first visible, enabled input inside auth section
            function isVisible(el){
                if (!el) return false;
                if (el.offsetWidth === 0 && el.offsetHeight === 0) return false;
                var style = window.getComputedStyle(el);
                if (style.visibility === 'hidden' || style.display === 'none') return false;
                return true;
            }

            var inputs = document.querySelectorAll('.mybazar-login-section input:not([type="hidden"]):not([disabled])');
            for (var i=0;i<inputs.length;i++){
                var el = inputs[i];
                if (isVisible(el)){
                    try { el.focus({ preventScroll: true }); if (el.select) el.select(); } catch(e){}
                    break;
                }
            }
        } catch (e) { /* noop */ }
    });
</script>
