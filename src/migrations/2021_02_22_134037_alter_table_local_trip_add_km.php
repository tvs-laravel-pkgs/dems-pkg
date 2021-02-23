<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableLocalTripAddKm extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('local_trip_visit_details', function (Blueprint $table) {
			$table->string('from_km',10)->nullable()->after('to_place');
			$table->string('to_km',10)->nullable()->after('from_km');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('local_trip_visit_details', function (Blueprint $table) {
			$table->dropColumn('from_km');
			$table->dropColumn('to_km');
		});
	}
}
