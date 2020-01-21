<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVisitsColumnAdd extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visits', function (Blueprint $table) {
			$table->time('prefered_departure_time')->nullable()->after('notes_to_agent');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('visits', function (Blueprint $table) {
			$table->dropColumn('prefered_departure_time');
		});
	}
}
