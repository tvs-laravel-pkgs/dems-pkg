<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVisits extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visits', function (Blueprint $table) {
			$table->dropForeign('visits_trip_id_foreign');
			$table->dropForeign('visits_to_city_id_foreign');
			$table->dropForeign('visits_from_city_id_foreign');
			$table->dropUnique('visit_trip_from_to_date');
			$table->dropColumn('date');
			$table->foreign('from_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('to_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade')->onUpdate('cascade');
			$table->unique(["trip_id", "from_city_id", "to_city_id", "departure_date"]);

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		//
	}
}
