<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDesignationNameCompamyIdUnique extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('designations', function (Blueprint $table) {
			$table->dropUnique('designations_name_unique');
			$table->unique(["name", "company_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('designations', function (Blueprint $table) {
			$table->dropUnique('designations_name_company_id_unique');
			$table->unique('name');
		});
	}
}
