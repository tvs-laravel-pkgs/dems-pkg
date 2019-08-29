<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivityLog extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('activity_logs', function (Blueprint $table) {
			$table->dateTime('date_time');
			$table->unsignedInteger('user_id');
			//$table->string('module', 191)->nullable();
			$table->unsignedInteger('entity_id')->nullable();
			$table->unsignedInteger('entity_type_id')->nullable();
			$table->unsignedInteger('activity_id')->nullable();
			$table->string('details', 255);
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('entity_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('activity_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('activity_logs');
	}
}
