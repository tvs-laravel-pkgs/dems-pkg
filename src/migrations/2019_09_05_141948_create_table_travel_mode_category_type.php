<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableTravelModeCategoryType extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('travel_mode_category_type', function (Blueprint $table) {
			$table->unsignedInteger('travel_mode_id');
			$table->unsignedInteger('category_id');
			$table->foreign('travel_mode_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('category_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.s
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('travel_mode_category_type');
	}
}
