<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsForDashCp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->date('end_date')->nullable();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->date('production_end_date')->nullable();
            $table->float('unplanned_hours')->default(0);
            $table->float('warranty_hours')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('production_end_date');
            $table->dropColumn('unplanned_hours');
            $table->dropColumn('warranty_hours');
        });
    }
}
