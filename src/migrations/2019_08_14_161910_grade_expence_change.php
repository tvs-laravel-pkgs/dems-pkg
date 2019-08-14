<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GradeExpenceChange extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('grade_expense_type', function (Blueprint $table) {
			$table->unsignedInteger('city_category_id')->nullable()->after('expense_type_id');
			$table->foreign('city_category_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('grade_expense_type', function (Blueprint $table) {
			$table->dropForeign('grade_expense_type_city_category_id_foreign');
			$table->dropColumn('city_category_id');
		});
	}
}
