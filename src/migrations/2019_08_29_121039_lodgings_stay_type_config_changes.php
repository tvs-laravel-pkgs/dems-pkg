<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LodgingsStayTypeConfigChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('lodgings', function (Blueprint $table) {
			$table->dropForeign('lodgings_stay_type_id_foreign');
			$table->foreign('stay_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
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
