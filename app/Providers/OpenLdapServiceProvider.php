<?php

namespace NeoClocking\Providers;

use Illuminate\Auth\Guard;
use Illuminate\Support\ServiceProvider;
use NeoClocking\Services\Ldap\OpenLdapUserProvider;

/**
 * An OpenLDAP authentication driver for Laravel 4.
 *
 * @author Yuri Moens (yuri.moens@gmail.com)
 */
class OpenLdapServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var boolean
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['auth']->extend('ldap', function ($app) {
            return new Guard(
                new OpenLdapUserProvider($app['db']->connection()),
                $app->make('session.store')
            );
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['ldap'];
    }
}
