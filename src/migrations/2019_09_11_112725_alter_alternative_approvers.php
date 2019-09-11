<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAlternativeApprovers extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('alternative_approvers', function (Blueprint $table) {
			$table->unsignedInteger('type')->change();
			$table->foreign('type')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('alternative_approvers', function (Blueprint $table) {
			$table->dropForeign('alternative_approvers_type_foreign');
		});
	}
}
