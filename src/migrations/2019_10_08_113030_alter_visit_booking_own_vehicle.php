<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterVisitBookingOwnVehicle extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->unsignedDecimal('km_start')->nullable()->after('paid_amount');
			$table->unsignedDecimal('km_end')->nullable()->after('km_start');
			$table->unsignedDecimal('toll_fee')->nullable()->after('km_end');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->dropColumn('km_start');
			$table->dropColumn('km_end');
			$table->dropColumn('toll_fee');
		});
	}
}
