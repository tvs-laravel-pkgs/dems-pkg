<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableReimbursementTranscations extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('reimbursement_transcations', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('outlet_id');
			$table->unsignedInteger('transcation_id');
			$table->dateTime('transaction_date');
			$table->unsignedInteger('transcation_type');
			$table->unsignedDecimal('amount', 10, 2);
			$table->unsignedDecimal('balance_amount', 10, 2);
			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('transcation_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		Schema::dropIfExists('reimbursement_transcations');
	}
}
