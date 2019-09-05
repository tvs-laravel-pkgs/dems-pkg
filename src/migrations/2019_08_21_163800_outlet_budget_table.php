<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OutletBudgetTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('outlet_budget', function (Blueprint $table) {
			$table->unsignedInteger('outlet_id');
			$table->unsignedInteger('sbu_id');
			$table->decimal('amount', 16, 2);
			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('sbu_id')->references('id')->on('sbus')->onDelete('cascade')->onUpdate('cascade');
			$table->unique(["outlet_id", "sbu_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('outlet_budget', function (Blueprint $table) {
			$table->dropForeign('outlet_budget_outlet_id_foreign');
			$table->dropForeign('outlet_budget_sbu_id_foreign');
		});
		Schema::dropIfExists('outlet_budget');
	}
}
