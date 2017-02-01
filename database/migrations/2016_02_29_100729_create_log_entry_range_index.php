<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogEntryRangeIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * Create an index to speed up searching by range. The function 'tsrange' converts the two field to an interval
         * The parenthesises/brackets for defining bounds are slightly confusing
         * The square brackets are inclusive and parenthesises are exclusive.
         * Thus '[)' includes the start and excludes the end.
         * This prevents 13:00 - 14:00 and 14:00 - 15:00 from being considered as overlapping.
         * More info here: http://www.postgresql.org/docs/9.4/static/rangetypes.html
         */
        DB::statement("create index log_entries_tsrange_idx on log_entries using gist(tsrange(started_at, ended_at, '[)'))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_entries', function (Blueprint $table) {
            $table->dropIndex('log_entries_tsrange_idx');
        });
    }
}
