<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableOutlet extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('outlets', function (Blueprint $table) {
			$table->unsignedDecimal('expense_voucher_limit', 10, 2)->nullable()->after('reimbursement_amount');
			$table->unsignedDecimal('minimum_threshold_balance', 10, 2)->nullable()->after('expense_voucher_limit');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('outlets', function (Blueprint $table) {
			$table->dropColumn('expense_voucher_limit');
			$table->dropColumn('minimum_threshold_balance');
		});
	}
}
