<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BoardingTableNewColumnChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('boardings', function (Blueprint $table) {
			$table->dropColumn('date');
			$table->date('from_date')->after('city_id');
			$table->date('to_date')->after('from_date');
			$table->unsignedTinyInteger('days')->after('to_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		//
	}
}
