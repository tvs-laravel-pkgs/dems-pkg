<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVisitBookings2 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->unsignedDecimal('paid_amount', 12, 2)->default(0)->after('total');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->dropColumn('paid_amount');
		});
	}
}
