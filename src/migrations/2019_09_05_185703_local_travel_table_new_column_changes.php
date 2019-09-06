<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LocalTravelTableNewColumnChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('local_travels', function (Blueprint $table) {
			$table->dropForeign('local_travels_city_id_foreign');
			$table->dropColumn('city_id');
			$table->dropColumn('eligible_amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
	}
}
