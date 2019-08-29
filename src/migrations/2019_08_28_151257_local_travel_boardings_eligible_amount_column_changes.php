<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LocalTravelBoardingsEligibleAmountColumnChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('boardings', function (Blueprint $table) {
			$table->unsignedDecimal('eligible_amount', 8, 2)->after('tax');
		});

		Schema::table('local_travels', function (Blueprint $table) {
			$table->string('from', 191)->change();
			$table->string('to', 191)->change();
			$table->unsignedDecimal('eligible_amount', 8, 2)->after('tax');
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
