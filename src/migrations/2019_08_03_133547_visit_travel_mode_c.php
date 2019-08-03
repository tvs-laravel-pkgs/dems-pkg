<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VisitTravelModeC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('visit_tavel_modes', function (Blueprint $table) {
			$table->unsignedInteger('visit_id');
			$table->unsignedInteger('travel_mode_id');

			$table->foreign('visit_id')->references('id')->on('visits')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('travel_mode_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["visit_id", "travel_mode_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('visit_tavel_modes');
	}
}
