<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use NeoClocking\Models\Status;

class ChangeStatusToBoolean extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('active')->default(true);
        });

        DB::statement(
            "UPDATE tasks
             set active = CASE
                WHEN status_id = 1
                  THEN TRUE
                  ELSE FALSE
             END"
        );

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('status_id');
        });

        Schema::drop('statuses');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->increments('id');

            $table->string('code');
            $table->string('name');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('status_id')->unsigned()->nullable();
            $table->foreign('status_id')->references('id')->on('statuses');
        });

        DB::statement(
            "INSERT INTO statuses (id, code, name)
             VALUES (1, 'actif', 'Actif'), (2, 'ferme', 'Fermé')"
        );

        DB::statement(
            "UPDATE tasks
             set status_id = CASE
                WHEN active = TRUE
                  THEN 1
                  ELSE 2
             END"
        );

        Schema::table('tasks', function (Blueprint $table) {
            $table ->integer('status_id')->unsigned()->nullable(false)->change();
            $table->dropColumn('active');
        });
    }
}
