@extends("layouts.app")

@section("content")
    <!-- FLEET MANAGEMENT TAB -->
    <div x-show="currentTab === 'fleet'">
        @include('pages.fleet')
    </div>

    <!-- CYBER OPS TAB -->
    <div x-show="currentTab === 'cyberops'" x-cloak>
        @include('pages.cyberops')
    </div>

    <!-- FORENSICS TAB -->
    <div x-show="currentTab === 'forensics'" x-cloak>
        @include('pages.forensics')
    </div>
@endsection
