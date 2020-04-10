<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCompanyAddCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('companies', function (Blueprint $table) {
			$table->tinyInteger('additional_approve')->default(0)->comment('1-Yes, 0-No')->after('reference_code');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('companies', function (Blueprint $table) {
			$table->dropColumn('additional_approve');
		});
	}
}
