<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSelfAxExortSynchedInLocalTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('local_trips', function (Blueprint $table) {
            $table->unsignedTinyInteger('self_ax_export_synched')->default(0)->comment('0-no,1-yes')->after('rejection_remarks');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_trips', function (Blueprint $table) {
            $table->dropColumn('self_ax_export_synched');
        });
    }
}
