<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEyEmployeeClaim extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('ey_employee_claims', function (Blueprint $table) {
			// $table->unsignedDecimal('transport_total', 10, 2)->nullable()->after('total_amount');
			// $table->unsignedDecimal('lodging_total', 10, 2)->nullable()->after('transport_total');
			// $table->unsignedDecimal('boarding_total', 10, 2)->nullable()->after('lodging_total');
			// $table->unsignedDecimal('local_travel_total', 10, 2)->nullable()->after('boarding_total');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->dropColumn('transport_total');
			$table->dropColumn('lodging_total');
			$table->dropColumn('boarding_total');
			$table->dropColumn('local_travel_total');
		});
	}
}
