<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EyEmployeeClaimsTableUpdateColumn extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->tinyInteger('is_deviation')->default(0)->comment('0 - No, 1 - Yes')->after('payment_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->dropColumn('is_deviation');
		});
	}
}
