<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LocalTravelsColumnChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('local_travels', function (Blueprint $table) {
			$table->dropForeign('local_travels_from_id_foreign');
			$table->dropForeign('local_travels_to_id_foreign');
			$table->renameColumn('from_id', 'from')->string('from', 191)->change();
			$table->renameColumn('to_id', 'to')->string('to', 191)->change();
			$table->unsignedInteger('city_id')->after('trip_id');
			$table->foreign('city_id')->references('id')->on('ncities')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('local_travels', function (Blueprint $table) {
			$table->dropForeign('local_travels_city_id_foreign');
			$table->dropColumn('city_id');
			// $table->dropColumn('from');
			// $table->dropColumn('to');
		});
	}
}
