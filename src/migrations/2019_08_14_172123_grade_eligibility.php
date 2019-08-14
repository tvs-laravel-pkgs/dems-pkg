<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GradeEligibility extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('grade_advanced_eligibility', function (Blueprint $table) {
			$table->unsignedInteger('grade_id');
			$table->foreign('grade_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->boolean('advanced_eligibility');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('grade_advanced_eligibility');
	}
}
