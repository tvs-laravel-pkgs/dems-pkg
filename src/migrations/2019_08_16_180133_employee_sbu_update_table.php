<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EmployeeSbuUpdateTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('employees', function (Blueprint $table) {
			$table->unsignedInteger('sbu_id')->nullable()->after('payment_mode_id');
			$table->foreign('sbu_id')->references('id')->on('sbus')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('employees', function (Blueprint $table) {
			$table->dropForeign('employees_sbu_id_foreign');
			$table->dropColumn('sbu_id');
		});
	}
}
