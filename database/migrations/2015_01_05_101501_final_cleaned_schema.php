<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class FinalCleanedSchema extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('number')->unsigned();
            $table->string('name');

            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');

            $table->string('number');
            $table->string('name');

            $table->integer('client_id')->unsigned();

            $table->boolean('active')->default(true);

            $table->integer('max_time')->unsigned();
            $table->boolean('require_comments')->default(false);

            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients');
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->increments('id');

            $table->string('code');
            $table->string('name');
        });

        Schema::create('reference_types', function (Blueprint $table) {
            $table->increments('id');

            $table->string('code');
            $table->string('name');
            $table->string('prefix')->nullable();
        });

        Schema::create('resource_types', function (Blueprint $table) {
            $table->increments('id');

            $table->string('code');
            $table->string('name');

            $table->timestamps();
        });

        if (config('database.default') != 'sqlite') {
            DB::statement("CREATE SEQUENCE task_number_seq");
        }

        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('number')->unsigned();
            $table->string('name');

            $table->integer('project_id')->unsigned();
            $table->integer('status_id')->unsigned();

            $table->integer('resource_type_id')->unsigned();

            $table->integer('reference_type_id')->unsigned()->nullable();
            $table->string('reference_number')->nullable();

            $table->integer('estimation')->unsigned()->nullable();
            $table->integer('revised_estimation')->unsigned()->nullable();
            $table->boolean('require_comments')->default(false);

            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects');
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('reference_type_id')->references('id')->on('reference_types');
            $table->foreign('resource_type_id')->references('id')->on('resource_types');
        });

        if (config('database.default') != 'sqlite') {
            DB::statement("ALTER TABLE tasks ALTER number SET DEFAULT NEXTVAL('task_number_seq')");
            DB::statement("ALTER SEQUENCE task_number_seq owned by tasks.number");
        }

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');

            $table->string('username');
            $table->string('mail');
            $table->boolean('active')->default(true);
            $table->string('first_name');
            $table->string('last_name');

            $table->integer('week_duration')->unsigned();
            $table->integer('hourly_cost');

            $table->string('api_key')->unique();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('favourite_tasks', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned();
            $table->integer('task_id')->unsigned();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('task_id')->references('id')->on('tasks');
        });

        Schema::create('log_entries', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned();
            $table->integer('task_id')->unsigned();

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->boolean('validated')->default(false);

            $table->text('comment')->nullable();
            $table->integer('hourly_cost');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('task_id')->references('id')->on('tasks');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('log_entries');

        Schema::drop('favourite_tasks');

        Schema::drop('users');

        Schema::drop('tasks');
        Schema::drop('statuses');
        Schema::drop('reference_types');
        Schema::drop('resource_types');

        Schema::drop('projects');

        Schema::drop('clients');
    }

}
