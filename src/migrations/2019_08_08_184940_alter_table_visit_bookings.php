<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVisitBookings extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->unsignedInteger('travel_mode_id')->nullable()->change();
		});
	}

	/**s
		     * Reverse the migrations.
		     *
		     * @return void
	*/
	public function down() {

	}
}
