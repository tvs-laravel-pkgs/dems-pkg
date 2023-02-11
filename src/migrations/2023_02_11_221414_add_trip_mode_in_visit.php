<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTripModeInVisit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('visits')) {
            Schema::table('visits', function (Blueprint $table) {
                if (!Schema::hasColumn('visits', 'trip_mode_id')) {
                    $table->unsignedInteger('trip_mode_id')->nullable()->after('agent_ax_export_synched');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('visits')) {
            Schema::table('visits', function (Blueprint $table) {
                if (Schema::hasColumn('visits', 'trip_mode_id')) {
                    $table->dropColumn('trip_mode_id');
                }
            });
        }
    }
}
