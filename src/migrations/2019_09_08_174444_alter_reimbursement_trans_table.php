<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterReimbursementTransTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('reimbursement_transcations', function (Blueprint $table) {
			$table->unsignedInteger('company_id')->default(1)->after('id');
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
		});

		// Schema::rename('petty_cash_employee_details', 'petty_cash_details');

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('reimbursement_transcations', function (Blueprint $table) {
			$table->dropForeign('reimbursement_transcations_company_id_foreign');
			$table->dropColumn('company_id');
		});
	}
}
