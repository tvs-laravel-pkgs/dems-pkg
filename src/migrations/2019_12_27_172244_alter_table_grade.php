<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGrade extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('grade_advanced_eligibility', function (Blueprint $table) {
			$table->unsignedInteger('local_trip_amount')->nullable()->after('four_wheeler_limit');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('grade_advanced_eligibility', function (Blueprint $table) {
			$table->dropColumn('local_trip_amount');
		});
	}
}
