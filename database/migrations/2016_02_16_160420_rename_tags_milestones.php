<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameTagsMilestones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign('tasks_tag_id_foreign');
            $table->renameColumn('tag_id', 'milestone_id');
        });

        Schema::rename('tags', 'milestones');

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('milestone_id')->references('id')->on('milestones');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign('tasks_milestone_id_foreign');
            $table->renameColumn('milestone_id', 'tag_id');
        });

        Schema::rename('milestones', 'tags');

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('tag_id')->references('id')->on('tags');
        });
    }
}
