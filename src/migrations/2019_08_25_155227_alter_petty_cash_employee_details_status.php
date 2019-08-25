<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPettyCashEmployeeDetailsStatus extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('petty_cash_employee_details', function (Blueprint $table) {
			$table->unsignedInteger('rejection_id')->nullable()->after('total');
			$table->string('remarks', 191)->nullable()->after('rejection_id');
			$table->unsignedInteger('status')->change();
			$table->foreign('rejection_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('status')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->timestamps();
			$table->softDeletes();
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('petty_cash_employee_details', function (Blueprint $table) {
			$table->dropForeign('petty_cash_employee_details_rejection_id_foreign');
			$table->dropForeign('petty_cash_employee_details_status_foreign');
			$table->dropColumn('remarks');
			$table->dropColumn('rejection_id');
			$table->dropColumn('created_at');
			$table->dropColumn('updated_at');
			$table->dropColumn('deleted_at');
		});
	}
}
