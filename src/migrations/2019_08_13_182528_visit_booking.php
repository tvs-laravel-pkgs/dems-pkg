<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VisitBooking extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->unsignedInteger('agent_claim_id')->nullable()->after('visit_id');
			$table->foreign('agent_claim_id')->references('id')->on('ey_agent_claims')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->dropForeign('visit_bookings_agent_claim_id_foreign');
			$table->dropColumn('agent_claim_id');
		});
	}
}
