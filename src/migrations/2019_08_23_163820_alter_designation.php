<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDesignation extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('designations', function (Blueprint $table) {
			$table->dropColumn('code');
			$table->unsignedInteger('company_id')->default(1)->after('id');
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('designations', function (Blueprint $table) {
			$table->dropForeign('designations_company_id_foreign');
			$table->dropColumn('company_id');
		});
	}
}
