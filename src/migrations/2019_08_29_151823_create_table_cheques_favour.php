<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableChequesFavour extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('cheque_details', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('detail_of_id');
			$table->unsignedInteger('entity_id');
			$table->string('cheque_favour', 191);
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
		Schema::dropIfExists('cheque_details');
	}
}
