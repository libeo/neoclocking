<?php

namespace NeoClocking\Providers;

use Dingo\Api\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'NeoClocking\Http\Controllers\Api';

    /**
     * Define the api routes for the application.
     *
     * @param  Router $router
     * @return void
     */
    public function map(Router $router)
    {
        $router->version('v1', function () use ($router) {
            $router->group(['namespace' => $this->namespace], function (Router $router) {
                require app_path('Http/api_routes.php');
            });
        });
    }
}
