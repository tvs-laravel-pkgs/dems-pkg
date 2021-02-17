<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGradeAddColOutAmount extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('grade_advanced_eligibility', function (Blueprint $table) {
			$table->unsignedInteger('outstation_trip_amount')->nullable()->after('local_trip_amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('grade_advanced_eligibility', function (Blueprint $table) {
			$table->dropColumn('outstation_trip_amount');
		});
	}
}
