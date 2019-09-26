<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGradeAdd extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('grade_advanced_eligibility', function (Blueprint $table) {
			$table->unsignedInteger('two_wheeler_per_km')->nullable()->after('travel_advance_limit');
			$table->unsignedInteger('four_wheeler_per_km')->nullable()->after('two_wheeler_per_km');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('grade_advanced_eligibility', function (Blueprint $table) {
			$table->dropColumn('two_wheeler_per_km');
			$table->dropColumn('four_wheeler_per_km');
		});
	}
}
