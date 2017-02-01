@extends('layouts.master')

@section('content-wrapper')
    <div class="l-content" role="main">
        <div class="l-content-wrapper is-single-column">
            @include('partial/messages')
            <div class="l-page-wrapper">
                <div class="l-block-content">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
@endsection

@section('define-vue')
    <script>
        $(function() {
            CLOCKING.Vues.initVue();
        });
    </script>
@endsection

@section('sidebar')
@endsection

@section('custom_script')
@endsection
