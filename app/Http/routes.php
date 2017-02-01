<?php

/** @var \Illuminate\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

$router->group(['middleware' => 'guest'], function () use ($router) {
    $router->get('/', 'AuthController@show')->name('login.show');
    $router->post('/', 'AuthController@login')->name('login');
});

/** LibéoDap Call this route when a project is updated.*/
$router->group(['prefix' => '/projects'], function () {
    get('/{projectNumber}/sync', 'ProjectController@syncWithLdap');
});

$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('logout', 'AuthController@logout')->name('logout');
    $router->get('control', 'AuthController@loginAs')->name('control_user');

    // Dashboard
    $router->get('/dashboard', 'DashboardController@show')->name('dashboard');

    // Projects
    $router->get('projects', 'ProjectController@index')->name('project.index');
    $router->get('projects/{projectNumber}/', 'ProjectController@show')->name('project.show');

    // Tasks
    $router->get('tasks/{taskNumber}/', 'TaskController@show')->name('task.show');
    $router->patch('tasks/{taskNumber}/update/', 'TaskController@update')->name('task.update');

});

// TODO Add authentication for this route (RM #39250)
$router->post('/event_listener/', 'EventListenerController@parseRequest');
