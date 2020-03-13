<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TripVisitsUniqueChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign('visits_trip_id_foreign');
            $table->dropForeign('visits_from_city_id_foreign');
            $table->dropForeign('visits_to_city_id_foreign');
            $table->dropUnique('visits_trip_id_from_city_id_to_city_id_departure_date_unique');                   
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('from_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('to_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
            $table->unique(["trip_id", "from_city_id", "to_city_id", "departure_date","travel_mode_id"],'visits_unique');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign('visits_trip_id_foreign');
            $table->dropForeign('visits_from_city_id_foreign');
            $table->dropForeign('visits_to_city_id_foreign');
            $table->dropForeign('visits_travel_mode_id_foreign');
            $table->dropUnique('visits_trip_id_from_city_id_to_city_id_departure_date_travel_mode_id_unique');
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('from_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('to_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('travel_mode_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
            $table->Unique(['trip_id,from_city_id,to_city_id,departure_date']);
        });
    }
}
