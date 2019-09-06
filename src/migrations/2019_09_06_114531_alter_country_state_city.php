<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCountryStateCity extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('countries', function (Blueprint $table) {
			$table->dropUnique('countries_code_unique');
			$table->dropUnique('countries_name_unique');
			$table->unsignedInteger('company_id')->default(1)->after('id');
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->unique(['company_id', 'code', 'name']);
		});

		Schema::table('ncities', function (Blueprint $table) {
			$table->dropForeign('ncities_state_id_foreign');
			$table->dropUnique('ncities_name_state_id_unique');
			$table->unsignedInteger('company_id')->default(1)->after('id');
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('state_id')->references('id')->on('nstates')->onDelete('cascade')->onUpdate('cascade');
			$table->unique(['company_id', 'name', 'state_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		//
	}
}
