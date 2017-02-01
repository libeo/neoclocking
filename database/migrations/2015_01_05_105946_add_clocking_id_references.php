<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddClockingIdReferences extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->integer('clocking_id')->unsigned()->nullable();
            $table->unique('clocking_id');
        });

        Schema::table('log_entries', function (Blueprint $table) {
            $table->integer('clocking_id')->unsigned()->nullable();
            $table->unique('clocking_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->integer('clocking_id')->unsigned()->nullable();
            $table->unique('clocking_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('clocking_id')->unsigned()->nullable();
            $table->unique('clocking_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('clocking_id')->unsigned()->nullable();
            $table->unique('clocking_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('clocking_id');
        });

        Schema::table('log_entries', function (Blueprint $table) {
            $table->dropColumn('clocking_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('clocking_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('clocking_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('clocking_id');
        });
    }

}
