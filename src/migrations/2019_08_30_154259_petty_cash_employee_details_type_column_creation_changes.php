<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PettyCashEmployeeDetailsTypeColumnCreationChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('petty_cash_employee_details', function (Blueprint $table) {
			$table->boolean('petty_cash_type')->default(1)->comment('1-Local, 2-Other')->after('expence_type');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('petty_cash_employee_details', function (Blueprint $table) {
			$table->dropColumn('petty_cash_type');
		});
	}
}
