<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TripsTableAdvanceRequestColumnChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('trips', function (Blueprint $table) {
			$table->unsignedInteger('advance_request_approval_status_id')->nullable()->after('advance_received');
			$table->foreign('advance_request_approval_status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('trips', function (Blueprint $table) {
			$table->dropForeign('trips_advance_request_approval_status_id_foreign');
			$table->dropColumn('advance_request_approval_status_id');
		});
	}
}
