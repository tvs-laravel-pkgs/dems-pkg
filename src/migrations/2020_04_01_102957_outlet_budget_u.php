<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OutletBudgetU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('outlet_budget', function (Blueprint $table) {
			$table->dropColumn('amount');
			$table->unsignedDecimal('outstation_budget_amount', 16, 2)->after('sbu_id');
			$table->unsignedDecimal('local_budget_amount', 16, 2)->after('outstation_budget_amount');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('outlet_budget', function (Blueprint $table) {
			$table->dropColumn('outstation_budget_amount');
			$table->dropColumn('local_budget_amount');
			$table->unsignedDecimal('amount', 16, 2)->after('sbu_id');
		});
	}
}
