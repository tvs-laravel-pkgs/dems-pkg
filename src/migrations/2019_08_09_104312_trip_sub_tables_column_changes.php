<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TripSubTablesColumnChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('lodgings', function (Blueprint $table) {
			$table->string('remarks', 255)->nullable()->after('eligible_amount');
		});

		Schema::table('boardings', function (Blueprint $table) {
			$table->string('remarks', 255)->nullable()->after('amount');
		});

		Schema::table('local_travels', function (Blueprint $table) {
			$table->string('description', 255)->nullable()->after('amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('lodgings', function (Blueprint $table) {
			$table->dropColumn('remarks');
		});

		Schema::table('boardings', function (Blueprint $table) {
			$table->dropColumn('remarks');
		});

		Schema::table('local_travels', function (Blueprint $table) {
			$table->dropColumn('description');
		});
	}
}
