@extends('layouts.app')

{{-- Page title --}}
@section('title')
    @lang('em.events')
@endsection

@section('content')

    <main>
        <router-view
            :date_format="{{ json_encode(
        [
            'vue_date_format' => format_js_date(),
            'vue_time_format' => format_js_time(),
        ],
        JSON_HEX_APOS,
    ) }}">
        </router-view>

    </main>
@endsection

@section('javascript')
    <script>
        var path = {!! json_encode($path, JSON_HEX_TAG) !!};
        var events_slider = false;
    </script>
    <script type="text/javascript" src="{{ static_asset('js/events_listing.js') }}"></script>


@stop
