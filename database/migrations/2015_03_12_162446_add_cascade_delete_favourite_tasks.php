<?php

use Illuminate\Database\Migrations\Migration;

class AddCascadeDeleteFavouriteTasks extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('favourite_tasks', function ($table) {
            $table->dropForeign('favourite_tasks_task_id_foreign');
            $table->dropForeign('favourite_tasks_user_id_foreign');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('favourite_tasks', function ($table) {
            $table->dropForeign('favourite_tasks_task_id_foreign');
            $table->dropForeign('favourite_tasks_user_id_foreign');

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('task_id')->references('id')->on('tasks');
        });
    }

}
