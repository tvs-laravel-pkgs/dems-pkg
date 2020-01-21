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
			$table->unsignedInteger('travel_advance_limit')->nullable()->after('claim_active_days');
			$table->unsignedInteger('two_wheeler_limit')->nullable()->after('travel_advance_limit');
			$table->unsignedInteger('four_wheeler_limit')->nullable()->after('two_wheeler_limit');
			$table->unsignedInteger('stay_type_disc')->change();

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
			$table->dropColumn('two_wheeler_limit');
			$table->dropColumn('four_wheeler_limit');
		});
	}
}
