<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableOutlets extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('outlets', function (Blueprint $table) {
			$table->dropColumn('cashier_name');
		});

		Schema::table('outlets', function (Blueprint $table) {
			$table->unsignedInteger('cashier_id')->nullable()->after('sbu_id');
			$table->decimal('reimbursement_amount', 16, 2)->nullable()->after('cashier_id');
			$table->foreign('cashier_id')->references('id')->on('employees')->onDelete('cascade')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('outlets', function (Blueprint $table) {
			$table->dropForeign('outlets_cashier_id_foreign');
			$table->dropColumn('cashier_id');
		});
	}
}
