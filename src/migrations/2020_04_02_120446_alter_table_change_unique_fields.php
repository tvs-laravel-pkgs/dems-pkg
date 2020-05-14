<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableChangeUniqueFields extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visits', function (Blueprint $table) {
			$table->dropForeign('visits_trip_id_foreign');
			$table->dropForeign('visits_from_city_id_foreign');
			$table->dropForeign('visits_to_city_id_foreign');
			$table->dropForeign('visits_booking_method_id_foreign');
			$table->dropUnique('visits_unique');
			$table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('from_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('to_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('booking_method_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->unique(["trip_id", "from_city_id", "to_city_id", "departure_date", "travel_mode_id", "booking_method_id"], 'visits_unique');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('visits', function (Blueprint $table) {
			$table->dropForeign('visits_trip_id_foreign');
			$table->dropForeign('visits_from_city_id_foreign');
			$table->dropForeign('visits_to_city_id_foreign');
			$table->dropForeign('visits_travel_mode_id_foreign');
			$table->dropForeign('visits_booking_method_id_foreign');
			$table->dropUnique('visits_unique');
			$table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('from_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('to_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('travel_mode_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('booking_method_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->unique(["trip_id", "from_city_id", "to_city_id", "departure_date", "travel_mode_id"], 'visits_unique');
		});
	}
}
