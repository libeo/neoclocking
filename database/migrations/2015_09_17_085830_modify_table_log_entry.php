<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ModifyTableLogEntry extends Migration
{


    /**
     *
     */
    public function up()
    {
        Schema::table('log_entries', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->integer('hourly_cost')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_entries', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->integer('hourly_cost')->change();
        });
    }
}
