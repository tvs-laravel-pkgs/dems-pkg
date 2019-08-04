<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VisitsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('visits', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('trip_id');
			$table->unsignedInteger('from_city_id');
			$table->unsignedInteger('to_city_id');
			$table->date('date');
			$table->unsignedInteger('travel_mode_id');
			$table->unsignedInteger('booking_method_id');
			$table->unsignedInteger('booking_status_id');
			$table->unsignedInteger('agent_id')->nullable();
			$table->string('notes_to_agent', 255)->nullable();
			$table->unsignedInteger('status_id');
			$table->unsignedInteger('manager_verification_status_id');

			$table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('from_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('to_city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('travel_mode_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('booking_method_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('booking_status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade')->onUpdate('cascade');

			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('manager_verification_status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["trip_id", "from_city_id", "to_city_id", "date"], 'visit_trip_from_to_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('visits');
	}
}
