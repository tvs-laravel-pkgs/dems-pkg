<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPettyCashTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('petty_cash', function (Blueprint $table) {
			$table->unsignedInteger('petty_cash_type_id')->after('id');
			$table->foreign('petty_cash_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});

		// Schema::rename('petty_cash_employee_details', 'petty_cash_details');

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
