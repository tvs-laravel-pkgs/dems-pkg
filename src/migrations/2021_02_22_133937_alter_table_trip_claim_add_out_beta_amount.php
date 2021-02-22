<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableTripClaimAddOutBetaAmount extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->unsignedInteger('total_trip_days')->nullable()->after('trip_id');
			$table->unsignedDecimal('beta_amount',16,2)->nullable()->after('local_travel_total');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->dropColumn('total_trip_days');
			$table->dropColumn('beta_amount');
		});
	}
}
