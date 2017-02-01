<!DOCTYPE html>
<html>
    <head lang="fr">
        <meta charset="UTF-8">
        <meta id="token" name="token" value="{{ csrf_token() }}">
        @if (isset($user))
            <meta id="X-Authorization" name="X-Authorization" value="{{ Auth::user()->api_key }}">
        @endif
        <title>@title('page-title', 'Néoclocking')</title>

        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <!-- Stylesheets -->
        <link rel="stylesheet" href="/css/main.css" type="text/css" media="screen">
        <link rel="stylesheet" href="/css/vendor.css" type="text/css" media="screen">

        <!-- Modernizr & Detectizr -->
        <script type="text/javascript" src="/js/vendor/modernizr.min.js"></script>
        <script type="text/javascript" src="/js/vendor/detectizr.min.js"></script>
        <script type="text/javascript" src="/js/vendor/svg4everybody.ie8.min.js"></script>

        <!-- Google Font Fallback -->
        <link href="https://fonts.googleapis.com/css?family=Roboto%7CRoboto:100%7CRoboto:300%7CRoboto:500%7CRoboto:700%7CRoboto+Condensed%7CRoboto+Condensed:300%7CRoboto+Condensed:700" rel="stylesheet" type="text/css">
        <link href="/css/font-awesome.css" rel="stylesheet" type="text/css">
        @yield('custom_style')
    </head>
    <body class="@section('body-classes') master @show" @yield('body-attr')>
        <div class="app-layout__interface">
            @include('partial.header')
            @yield('content-wrapper')
            @include('partial/task-actions')
        </div>
        <script src="/js/bundle.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pickadate.js/3.5.6/compressed/picker.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pickadate.js/3.5.6/compressed/picker.date.js"></script>
        <script>
            /** FIX: https://github.com/amsul/pickadate.js/issues/709 */
            $.fn.pickatime = { defaults: {} }
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pickadate.js/3.5.6/compressed/translations/fr_FR.js"></script>

        @yield('define-vue')
        @yield('custom_script')
    </body>
</html>
