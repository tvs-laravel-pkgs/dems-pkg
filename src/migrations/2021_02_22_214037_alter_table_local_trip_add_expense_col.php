<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableLocalTripAddExpenseCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('local_trips', function (Blueprint $table) {
			$table->unsignedDecimal('travel_amount',16,2)->nullable()->after('end_date');
			$table->renameColumn('other_amount', 'other_expense_amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('local_trips', function (Blueprint $table) {
			$table->dropColumn('travel_amount');
			$table->renameColumn('other_expense_amount', 'other_amount');
		});
	}
}
