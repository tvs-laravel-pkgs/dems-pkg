<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVisitBookings3 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->unsignedInteger('booking_type_id')->nullable()->after('travel_mode_id');
			$table->foreign('booking_type_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.s
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->dropForeign('visit_bookings_booking_type_id_foreign');
			$table->dropColumn('booking_type_id');
		});
	}
}
