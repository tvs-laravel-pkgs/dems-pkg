<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableApprovalLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('approval_logs', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('type_id');
			$table->unsignedInteger('entity_id');
			$table->unsignedInteger('approval_type_id');
			$table->unsignedInteger('approved_by_id');
			$table->timestamp('approved_at');
			$table->foreign('type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('approval_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('approved_by_id')->references('id')->on('employees')->onDelete('cascade')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('approval_logs');
	}
}
