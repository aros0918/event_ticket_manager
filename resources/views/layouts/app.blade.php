<!doctype html>
<html class="no-js" lang="{{ str_replace('_', '-', app()->getLocale()) }}" {!! is_rtl() ? 'dir="rtl"' : '' !!}>

<head>

    @include('layouts.meta')

    @include('layouts.favicon')

    @include('layouts.include_css')

    @yield('stylesheet')
</head>

<body class="home" {!! is_rtl() ? 'dir="rtl"' : '' !!}>

    <!--[if lt IE 8]>
    <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade
        your browser</a> to improve your experience.</p>
    <![endif]-->

    {{-- Ziggy directive --}}
    @routes

    {{-- Main wrapper --}}
    <div id="event_laravel_app">

        @include('layouts.header')

        @php
$no_breadcrumb = [
    'welcome',
    'events_show',
    'show_login',
    'register',
    'show_register',
    'password_request',
    'password_reset_show',
    'myevents_index',
    'myevents_form',
];
        @endphp
        @if (!in_array(Route::currentRouteName(), $no_breadcrumb))
            @include('layouts.breadcrumb')
        @endif

        <section class="db-wrapper">

            {{-- page content --}}
            @yield('content')

            {{-- set progress bar --}}
            <vue-progress-bar></vue-progress-bar>
        </section>

        @include('layouts.footer')

    </div>
    <!--Main wrapper end-->

    @include('layouts.include_js')

    {{-- Page specific javascript --}}
    @yield('javascript')

</body>

</html>
