<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VisitsSubTablesExpensesColumnChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->unsignedDecimal('tax', 8, 2)->nullable()->change();
			$table->string('remarks', 255)->nullable()->after('tax');
		});

		Schema::table('lodgings', function (Blueprint $table) {
			$table->unsignedDecimal('tax', 8, 2)->nullable()->change();
			$table->string('reference_number', 191)->nullable()->change();
			$table->string('lodge_name', 191)->nullable()->change();
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
