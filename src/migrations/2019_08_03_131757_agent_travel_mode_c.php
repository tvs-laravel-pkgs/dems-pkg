<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AgentTravelModeC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('agent_travel_mode', function (Blueprint $table) {
			$table->unsignedInteger('agent_id');
			$table->unsignedInteger('travel_mode_id');

			$table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('travel_mode_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["agent_id", "travel_mode_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('agent_travel_mode');

	}
}
