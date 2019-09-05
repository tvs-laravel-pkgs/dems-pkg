<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableDesignation extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('designations', function (Blueprint $table) {
			/*$table->unsignedInteger('grade_id')->nullable()->after('name');
			$table->foreign('grade_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');*/
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		/*Schema::table('designations', function (Blueprint $table) {
			$table->dropForeign('designations_grade_id_foreign');
			$table->dropColumn('grade_id');
		});*/
	}
}
