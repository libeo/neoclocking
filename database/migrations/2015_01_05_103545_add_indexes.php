<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddIndexes extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('favourite_tasks', function (Blueprint $table) {
            $table->index('user_id');
            $table->unique(
                [
                    'user_id',
                    'task_id',
                ]
            );
        });

        Schema::table('log_entries', function (Blueprint $table) {
            $table->index('user_id');

            $table->index('started_at');
            $table->index('ended_at');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->unique('number');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('name');
            $table->unique('number');
            $table->index('number');

            $table->index('client_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('name');
            $table->unique('number');
            $table->index('number');

            $table->index('project_id');

            $table->index('reference_type_id');
            $table->index('reference_number');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
            $table->index('username');
        });

        Schema::table('reference_types', function (Blueprint $table) {
            $table->unique('code');
        });

        Schema::table('resource_types', function (Blueprint $table) {
            $table->unique('code');
        });

        Schema::table('statuses', function (Blueprint $table) {
            $table->unique('code');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No down...
    }

}
