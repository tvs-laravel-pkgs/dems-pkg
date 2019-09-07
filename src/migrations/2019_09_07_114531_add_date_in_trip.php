<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateInTrip extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('trips', function (Blueprint $table) {
			$table->date('start_date');
			$table->date('end_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('trips', function (Blueprint $table) {
			$table->dropColumn('start_date');
			$table->dropColumn('end_date');
		});
	}
}
