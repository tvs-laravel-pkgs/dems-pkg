<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterNcitiesTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('ncities', function (Blueprint $table) {
			$table->unsignedInteger('category_id')->nullable()->after('name');
			$table->foreign('category_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('ncities', function (Blueprint $table) {
			$table->dropForeign('ncities_category_id_foreign');
		});
	}
}
