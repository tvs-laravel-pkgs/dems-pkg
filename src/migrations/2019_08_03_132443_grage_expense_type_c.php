<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GrageExpenseTypeC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('grade_expense_type', function (Blueprint $table) {
			$table->unsignedInteger('grade_id');
			$table->unsignedInteger('expense_type_id');
			$table->unsignedInteger('city_category_id');
			$table->unsignedDecimal('eligible_amount', 10, 2);

			$table->foreign('grade_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('expense_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["grade_id", "expense_type_id", "city_category_id"], 'grd_exp_type');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('grade_expense_type');
	}
}
