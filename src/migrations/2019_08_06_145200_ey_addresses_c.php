<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EyAddressesC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('ey_addresses', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('address_of_id');
			$table->unsignedInteger('entity_id');
			$table->string('name', 255);
			$table->string('line_1', 255);
			$table->string('line_2', 255)->nullable();
			$table->unsignedInteger('country_id');
			$table->unsignedInteger('state_id');
			$table->unsignedInteger('city_id');
			$table->string('pincode', 6);
			$table->string('lat', 20)->nullable();
			$table->string('lng', 20)->nullable();

			$table->foreign('address_of_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');

			$table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('state_id')->references('id')->on('nstates')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('ey_addresses');
	}
}
