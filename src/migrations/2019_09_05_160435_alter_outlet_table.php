<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterOutletTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('outlets', function (Blueprint $table) {
			$table->tinyInteger('claim_req_approver')->nullable()->after('sbu_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('outlets', function (Blueprint $table) {
			$table->dropColumn('claim_req_approver');
		});
	}
}
