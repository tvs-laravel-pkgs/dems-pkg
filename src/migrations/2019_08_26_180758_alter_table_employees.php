<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableEmployees extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('employees', function (Blueprint $table) {
			$table->string('gender', 10)->nullable()->after('sbu_id');
			$table->date('date_of_birth')->nullable()->after('gender');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('employees', function (Blueprint $table) {
			$table->dropColumn('gender');
			$table->dropColumn('date_of_birth');
		});
	}
}
