<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGradeAdvance extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('grade_advanced_eligibility', function (Blueprint $table) {
			$table->unsignedInteger('claim_active_days')->default(5)->after('deviation_eligiblity');
			$table->unsignedDecimal('travel_advance_limit', 10, 2)->nullable()->after('claim_active_days');
			$table->unsignedInteger('two_wheeler_limit')->nullable()->after('travel_advance_limit');
			$table->unsignedInteger('four_wheeler_limit')->nullable()->after('two_wheeler_limit');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('grade_advanced_eligibility', function (Blueprint $table) {
			$table->dropColumn('claim_active_days');
			$table->dropColumn('travel_advance_limit');
		});
	}
}
