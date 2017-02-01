@extends('layouts.master')

@section('content-wrapper')
    <div class="l-content app-layout__page" role="main">
        <div class="l-content-wrapper is-two-columns app-layout__page">
            <div class="l-page-wrapper app-layout__page__content">
                <div class="l-block-content">
                    @yield('content')
                </div>
                <div class="l-block-sidebar">
                    @yield('sidebar')
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


