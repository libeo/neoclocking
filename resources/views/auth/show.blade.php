@extends('layouts.one-column')

@section('body-classes') login in-project @endsection

@section('content')
    <div class="projects">
        <div class="projects-header">
            <h1>Connexion NéoClocking</h1>
        </div>
        @if ($errors->any())
            <div class="login-form-errors alert alert-danger">
                <ul class="errors">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('login') }}" method="post" class="login-form">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="login-input-wrapper is-fullwidth">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" name="username" id="username" value="{{ old('username') }}" autofocus>
            </div>

            <div class="login-input-wrapper is-fullwidth">
                <label for="password">Mot de passe</label>
                <input type="password" name="password" id="password">
            </div>

            <div class="login-input-wrapper is-fullwidth">
                <button type="submit" class="button is-gradient-purple">Connexion</button>
            </div>
        </form>
    </div>
@endsection

@section('custom_script')
@endsection
