<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVisitBookings4 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->unsignedDecimal('cgst', 10, 2)->nullable()->after('tax');
			$table->unsignedDecimal('sgst', 10, 2)->nullable()->after('cgst');
			$table->unsignedDecimal('igst', 10, 2)->nullable()->after('sgst');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->dropColumn('cgst');
			$table->dropColumn('sgst');
			$table->dropColumn('igst');
		});
	}
}
