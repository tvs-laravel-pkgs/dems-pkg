<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('employees', function (Blueprint $table) {
			$table->dropColumn('name');
		});
		Schema::table('agents', function (Blueprint $table) {
			$table->dropColumn('name');
		});
		Schema::table('users', function (Blueprint $table) {
			$table->string('name', 255)->nullable()->after('username');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('name');
		});
	}
}
