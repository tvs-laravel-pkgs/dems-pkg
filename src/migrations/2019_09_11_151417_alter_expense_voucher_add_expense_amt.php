<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterExpenseVoucherAddExpenseAmt extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('expense_voucher_advance_requests', function (Blueprint $table) {
			$table->unsignedDecimal('expense_amount', 10, 2)->nullable()->after('advance_amount');
			$table->unsignedInteger('rejection_id')->nullable()->after('description');
			$table->foreign('rejection_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->string('remarks', 191)->nullable()->after('rejection_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('expense_voucher_advance_requests', function (Blueprint $table) {
			$table->dropColumn('expense_amount');
			$table->dropColumn('expense_voucher_advance_requests_rejection_id_foreign');
			$table->dropColumn('rejection_id');
			$table->dropColumn('remarks');
		});
	}
}
