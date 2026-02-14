<!DOCTYPE html>
@if (app()->getLocale() == 'ar')
<html lang="ar" dir="rtl">
@else
<html lang="en" dir="auto">
@endif
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        @hasSection('title')
            @yield('title') |
        @endif {{ get_option('general')['title'] ?? config('app.name') }}
    </title>
    @include('layouts.web.partials.css')
</head>

<body>
    @if (request()->is('/'))
        @include('layouts.web.partials.header')
    @else
        @include('layouts.web.partials.common_header')
    @endif

    @yield('main_content')

    @include('layouts.web.partials.footer')

    <input type="hidden" id="payment_success" value="{{ session('payment_success') }}">

    @include('layouts.web.partials.script')
    <script>
        AOS.init({
            once: false,
            duration: 800,
        });
    </script>

</body>

</html>
