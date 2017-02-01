<?php

use NeoClocking\Utilities\KeyGenerator;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(NeoClocking\Models\User::class, function (Faker\Generator $faker) {
    return [
        'username' => $faker->username,
        'first_name' => $faker->name,
        'last_name' => $faker->name,
        'hourly_cost'   => (20 * 100),
        'week_duration' => (37.5 * 60),
        'mail' => $faker->email,
        'api_key' => KeyGenerator::generateRandomKey(),
    ];
});
