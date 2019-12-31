<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableAddColumn extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->dateTime('claim_approval_datetime')->nullable()->after('payment_id');
		});
		Schema::table('local_trips', function (Blueprint $table) {
			$table->dateTime('claim_approval_datetime')->nullable()->after('payment_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->dropColumn('claim_approval_datetime');
		});
		Schema::table('local_trips', function (Blueprint $table) {
			$table->dropColumn('claim_approval_datetime');
		});
	}
}
