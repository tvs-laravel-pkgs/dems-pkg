<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WalletDetailsCreateTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('wallet_details', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('wallet_of_id');
			$table->foreign('wallet_of_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->unsignedInteger('entity_id');
			$table->unsignedInteger('type_id');
			$table->foreign('type_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->string('value', 50);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('wallet_details');
	}
}
