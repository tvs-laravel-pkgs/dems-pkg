<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableRejectReasons extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('trips', function (Blueprint $table) {
			$table->unsignedInteger('rejection_id')->nullable()->after('payment_id');
			$table->string('rejection_remarks', 191)->nullable()->after('rejection_id');
			$table->foreign('rejection_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
		});

		Schema::table('ey_agent_claims', function (Blueprint $table) {
			$table->unsignedInteger('rejection_id')->nullable()->after('payment_id');
			$table->string('rejection_remarks', 191)->nullable()->after('rejection_id');
			$table->foreign('rejection_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('trips', function (Blueprint $table) {
			$table->dropForeign('trips_rejection_id_foreign');
			$table->dropColumn('rejection_id');
			$table->dropColumn('rejection_remarks');
		});

		Schema::table('ey_agent_claims', function (Blueprint $table) {
			$table->dropForeign('trips_rejection_id_foreign');
			$table->dropColumn('rejection_id');
			$table->dropColumn('rejection_remarks');
		});
	}
}
