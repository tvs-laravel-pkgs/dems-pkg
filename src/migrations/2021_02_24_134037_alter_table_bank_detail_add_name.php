<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableBankDetailAddName extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('bank_details', function (Blueprint $table) {
			$table->string('account_name',100)->nullable()->after('branch_name');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('bank_details', function (Blueprint $table) {
			$table->dropColumn('account_name');
		});
	}
}
