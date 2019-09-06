<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OutletSbuPettyColumnChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('outlets', function (Blueprint $table) {
			$table->unsignedInteger('sbu_id')->nullable()->after('name');
			$table->string('cashier_name', 191)->nullable()->after('sbu_id');
			$table->tinyInteger('amount_eligible')->nullable()->after('cashier_name');
			$table->unsignedDecimal('amount_limit')->nullable()->after('amount_eligible');
			$table->foreign('sbu_id')->references('id')->on('sbus')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('outlets', function (Blueprint $table) {
			$table->dropForeign('outlets_sbu_id_foreign');
			$table->dropColumn('sbu_id');
			$table->dropColumn('cashier_name');
			$table->dropColumn('amount_eligible');
			$table->dropColumn('amount_limit');
		});
	}
}
