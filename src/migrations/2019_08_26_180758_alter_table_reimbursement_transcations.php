<?php

use Illuminate\Database\Migrations\Migration;

class AlterTableReimbursementTranscations extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('reimbursement_transcations', function (Blueprint $table) {
			$table->unsignedInteger('petty_cash_id')->nullable()->after('transcation_id');
			$table->foreign('petty_cash_id')->references('id')->on('petty_cash')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('reimbursement_transcations', function (Blueprint $table) {
			$table->dropForeign('reimbursement_transcations_petty_cash_id_foreign');
			$table->dropColumn('petty_cash_id');
		});
	}
}
