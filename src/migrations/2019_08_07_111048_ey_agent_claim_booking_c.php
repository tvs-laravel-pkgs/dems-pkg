<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EyAgentClaimBookingC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::dropIfExists('ey_agent_claim_booking');
		Schema::create('ey_agent_claim_booking', function (Blueprint $table) {
			$table->unsignedInteger('agent_claim_id');
			$table->unsignedInteger('booking_id');

			$table->foreign('agent_claim_id')->references('id')->on('ey_agent_claims')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('booking_id')->references('id')->on('visit_bookings')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["agent_claim_id", "booking_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('ey_agent_claim_booking');
	}
}
