<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableLocalTripVisitDetails extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('local_trip_visit_details', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('trip_id');
			$table->unsignedInteger('travel_mode_id');
			$table->date('travel_date');
			$table->string('from_place', 191);
			$table->string('to_place', 191);
			$table->unsignedDecimal('amount', 10, 2);
			$table->unsignedDecimal('extra_amount', 10, 2)->nullable();
			$table->string('description', 191);
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('trip_id')->references('id')->on('local_trips')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('travel_mode_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
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
		Schema::dropIfExists('local_trip_visit_details');
	}
}
