<?php

/** @var Dingo\Api\Routing\Router $router */

use NeoClocking\Models\Client;
use NeoClocking\Models\Project;
use NeoClocking\Models\User;

$router->get('/', function () {
    try {
        $api_doc = realpath(base_path('doc/blueprint/api.html'));
        return file_get_contents($api_doc);
    } catch (\Exception $e) {
        return 'Voir la documentation du projet pour générer la documentation.';
    }
});

// route to obtain api key of a user with a username password
$router->post('user-auth', 'UserController@userAuth')->name('api.userAuth');

$router->group(['middleware' => 'auth.api'], function () use ($router) {
    // Projects
    $router->get('projects', 'ProjectController@index');
    $router->get('projects/lists', 'ProjectController@lists');
    $router->get('projects/{number}', 'ProjectController@show');

    // Project Tasks
    $router->get('projects/{project_number}/tasks', 'ProjectController@tasks')->name('api.projects.tasks');
    $router->put('projects/{project_number}/tasks', 'ProjectController@updateTasks')->name('api.project_tasks.update');
    $router->delete('projects/{project_number}/tasks', 'ProjectController@deleteTasks')->name('api.project_tasks.delete');

    // Project Milestones
    $router->get('projects/{project_number}/milestones', 'ProjectController@milestones')
        ->name('api.projects.milestones');
    $router->post('projects/{project_number}/milestones', 'ProjectController@createMilestone')
        ->name('api.projects.milestones.create');

    // Tasks
    $router->get('tasks', 'TaskController@index');
    $router->get('tasks/{task_number}', 'TaskController@show');
    $router->patch('tasks/{task_number}', 'TaskController@update');
    $router->delete('tasks/{task_number}', 'TaskController@destroy');
    $router->post('tasks/{task_number}/access', 'TaskController@askAccess');

    // Live entries
    $router->get('live-entries', 'LiveEntryController@show')->name('api.live-entries.show');
    $router->post('live-entries', 'LiveEntryController@store')->name('api.live-entries.store');
    $router->patch('live-entries', 'LiveEntryController@update')->name('api.live-entries.update');
    $router->delete('live-entries', 'LiveEntryController@destroy')->name('api.live-entries.delete');

    // Users
    $router->get('users', 'UserController@index')->name('api.users');
    $router->get('users/{username}', 'UserController@show')->name('api.user');
    $router->get('users/{username}/workedTime', 'UserController@workedTime')->name('api.worked-time');
    $router->get('users/{username}/timeRemainingThisWeek', 'UserController@timeRemainingThisWeek')
        ->name('api.time-remaining');
    $router->get('users/{username}/timePerDay', 'UserController@timePerDay')
        ->name('api.time-remaining');

    // Log entries
    $router->resource('log-entries', 'LogEntryController');

    // Current user favourites
    $router->get('favourite-tasks', 'FavouriteTaskController@index')->name('api.favourite');
    $router->post('favourite-tasks', 'FavouriteTaskController@store')->name('api.add-favourite');
    $router->delete('favourite-tasks', 'FavouriteTaskController@destroy')->name('api.delete-favourite');

    // Reference types
    $router->get('reference-types', 'ReferenceTypeController@index');

    // Resource types
    $router->get('resource-types', 'ResourceTypeController@index');

    // Clients
    //$router->resource('clients', 'ClientController', ['only' => ['index', 'show']]); // Not used
    //$router->get('clients/{id}/projects', 'ClientController@listProjects')
    //    ->name('api.clients.projects')->where('id', '[0-9]+'); // Not used

    $router->group(['prefix' => 'sync', 'middleware' => 'auth.lbodap'], function () use ($router) {
        $router->get('project/{project}', 'SyncController@project');
        $router->get('user/{user}', 'SyncController@user');
        $router->get('user-permissions/{permission}', 'SyncController@userPermissions');
        $router->get('client/{client}', 'SyncController@client');
    });
});

Route::bind('project', function ($number) {
    return Project::firstOrNew(compact('number'));
});

Route::bind('user', function ($username) {
    return User::firstOrNew(compact('username'));
});

Route::bind('client', function ($number) {
    return Client::firstOrNew(compact('number'));
});
