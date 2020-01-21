<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EyEmployeeClaimTableChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->enum('amount_to_pay', ['1', '2'])->comment('1 - Financier, 2 - Employee')->nullable()->after('total_amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->dropColumn('amount_to_pay');
		});
	}
}
