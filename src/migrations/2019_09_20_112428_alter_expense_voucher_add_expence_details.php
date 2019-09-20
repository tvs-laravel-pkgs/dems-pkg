<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterExpenseVoucherAddExpenceDetails extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('expense_voucher_advance_requests', function (Blueprint $table) {
			$table->string('expense_description', 191)->nullable()->after('description');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('expense_voucher_advance_requests', function (Blueprint $table) {
			$table->dropColumn('expense_description');
		});
	}
}
