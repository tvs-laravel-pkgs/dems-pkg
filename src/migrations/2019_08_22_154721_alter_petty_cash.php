<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPettyCash extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('petty_cash', function (Blueprint $table) {
			$table->unsignedInteger('purpose_id')->nullable()->change();
			$table->unsignedInteger('travel_mode_id')->nullable()->change();
			$table->string('from_place', 191)->nullable()->change();
			$table->string('to_place', 191)->nullable()->change();
			$table->unsignedDecimal('from_KM_reading')->nullable()->change();
			$table->unsignedDecimal('to_KM_reading')->nullable()->change();
			$table->unsignedDecimal('amount')->nullable()->after('to_KM_reading');
			$table->unsignedDecimal('tax')->nullable()->after('amount');
			$table->string('details', 191)->nullable()->after('tax');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('petty_cash', function (Blueprint $table) {
			$table->dropColumn('amount');
			$table->dropColumn('tax');
			$table->dropColumn('details');
		});
	}
}
