<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TravelPurposeGrade extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('grade_trip_purpose', function (Blueprint $table) {
			$table->unsignedInteger('trip_purpose_id');
			$table->unsignedInteger('grade_id');

			$table->foreign('trip_purpose_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('grade_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["trip_purpose_id", "grade_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('grade_trip_purpose');
	}
}
