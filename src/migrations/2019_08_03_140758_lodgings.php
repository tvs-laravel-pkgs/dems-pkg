<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Lodgings extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('lodgings', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('trip_id');
			$table->unsignedInteger('city_id');
			$table->datetime('check_in_date');
			$table->datetime('checkout_date');
			$table->unsignedTinyInteger('stayed_days');
			$table->unsignedInteger('stay_type_id');
			$table->unsignedDecimal('amount');
			$table->unsignedDecimal('tax');
			$table->unsignedDecimal('total');
			$table->string('reference_number', 191);
			$table->string('description', 255)->nullable();
			$table->string('lodge_name');
			$table->unsignedDecimal('eligible_amount');
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('stay_type_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('lodgings');
	}
}
