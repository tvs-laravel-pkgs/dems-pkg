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
		Schema::create('travel_purpose_grade', function (Blueprint $table) {
			$table->unsignedInteger('travel_purpose_id');
			$table->unsignedInteger('grade_id');

			$table->foreign('travel_purpose_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('grade_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["travel_purpose_id", "grade_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('travel_purpose_grade');
	}
}
