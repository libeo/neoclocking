<header class="l-header">
    <div class="l-page-wrapper">
        <div class="l-header-top">
            <div class="l-header-top-left">
                <a href="{{ URL::route('dashboard') }}" class="logo">
                    <?php include(app_path().'/../resources/assets/svg/logo.svg') ?>
                </a>
            </div>
            @if (isset($user))
                <div class="l-header-top-right {{ ($user->originalUser) ? 'is-controlling' : '' }}" >
                    <div class="time-total-wrapper l-header-top-right-element">
                        <span class="time-total-label">
                            <span>Temps restant<br> cette semaine</span>
                            <span class="icon-wrapper time-wrapper">
                                <svg width="20" height="20" class="time">
                                    <use xlink:href="/svg/symbols.svg#time"></use>
                                </svg>
                            </span>
                        </span>
                        <time-remaining />
                    </div>
                    <div class="profile-wrapper l-header-top-right-element">
                        @if(Route::is('dashboard'))
                            <span class="profile-trigger">
                                <img src="{{ $user->gravatar() }}" alt="{{ $user->present()->fullName() }}" title="{{ $user->present()->fullName() }}">
                            </span>
                        @else
                            <a class="profile-trigger" href="{{ route('dashboard') }}">
                                <img src="{{ $user->gravatar() }}" alt="{{ $user->present()->fullName() }}" title="{{ $user->present()->fullName() }}">
                            </a>
                        @endif
                    </div>
                    @if ($user->originalUser)
                        <div class="menu-wrapper l-header-top-right-element">
                            <a class="reset-control" href="{{ route('logout') }}">&times;</a>
                        </div>
                    @else
                        <div class="menu-wrapper l-header-top-right-element">
                            <button class="menu-trigger" data-menuopener>
                                <span class="icon-wrapper menu-wrapper">
                                    <svg width="32" height="23" class="menu">
                                        <use xlink:href="/svg/symbols.svg#menu"></use>
                                    </svg>
                                </span>
                            </button>
                            <div class="menu-content" data-menu>
                                <div class="menu-content-wrapper">
                                    <div class="menu-content-header">
                                        <div class="menu-content-header-left">
                                            <a class="menu-content-logout" href="{{ Route('logout') }}">Déconnexion</a>
                                        </div><!-- pour inline block
                                        --><div class="menu-content-header-right">
                                            <button class="menu-closer" data-menucloser>&times;</button>
                                        </div>
                                    </div>
                                    <nav class="menu-content-nav">
                                        <div class="menu-content-nav-item @if(Route::is('dashboard')) hidden @endif">
                                            <a href="{{ Route('dashboard') }}" class="button is-gradient-purple @if(Route::is('dashboard')) active @endif">Tableau de bord</a>
                                        </div>
                                        <div class="menu-content-nav-item @if(Route::is('dashboard')) hidden @endif">
                                            <a href="{{ Route('project.index') }}" class="button is-gradient-purple @if(Route::is('project.index')) active @endif">Liste des Projets</a>
                                        </div>
                                    </nav>
                                    @if($user->canControlUsers())
                                        <groups-users />
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
        @yield('extra-header')
    </div>
</header>

@include('partial/groups-users')
@include('partial/user-list')

@section('header-search-bar')
    @if (isset($user))
        @include('partial/search')
    @endif
@show
