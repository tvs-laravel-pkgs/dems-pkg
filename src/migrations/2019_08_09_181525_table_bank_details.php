<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableBankDetails extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('bank_details', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('detail_of_id');
			$table->unsignedInteger('entity_id');
			$table->string('bank_name', 100);
			$table->string('branch_name', 50)->nullable();
			$table->string('account_number', 20);
			$table->string('ifsc_code', 10);
			$table->unsignedInteger('account_type_id');
			$table->foreign('detail_of_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('account_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('bank_details');
	}
}
