<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableAgent extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('agents', function (Blueprint $table) {
			$table->unsignedInteger('payment_mode_id')->nullable()->after('name');
			$table->foreign('payment_mode_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('agents', function (Blueprint $table) {
			$table->dropForeign('agents_payment_mode_id_foreign');
			$table->dropColumn('payment_mode_id');
		});
	}
}
