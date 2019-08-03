<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LocalTravels extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('local_travels', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('trip_id');
			$table->unsignedInteger('mode_id');
			$table->date('date');
			$table->unsignedInteger('from_id');
			$table->unsignedInteger('to_id');
			$table->unsignedDecimal('amount');
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('mode_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('from_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('to_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
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
		Schema::dropIfExists('local_travels');
	}
}
