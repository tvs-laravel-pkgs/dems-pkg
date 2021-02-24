<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableLocalTripChangeExpenseCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('local_trips', function (Blueprint $table) {
			$table->unsignedDecimal('beta_amount',16,2)->change()->nullable();
			$table->unsignedDecimal('other_expense_amount',16,2)->change()->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('local_trips', function (Blueprint $table) {
			$table->unsignedDecimal('beta_amount',16,2)->change()->nullable();
			$table->unsignedDecimal('other_expense_amount',16,2)->change()->nullable();
		});
	}
}
