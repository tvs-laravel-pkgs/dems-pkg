<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClaimSubTableColumnChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->string('gstin', 15)->nullable()->after('tax');
		});

		Schema::table('lodgings', function (Blueprint $table) {
			$table->string('gstin', 15)->nullable()->after('tax');
		});

		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->tinyInteger('is_justify_my_trip')->nullable()->comment('0 - No, 1 - Yes')->after('is_deviation');
			$table->string('remarks', 255)->nullable()->after('is_justify_my_trip');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('visit_bookings', function (Blueprint $table) {
			$table->dropColumn('gstin');
		});

		Schema::table('lodgings', function (Blueprint $table) {
			$table->dropColumn('gstin');
		});

		Schema::table('ey_employee_claims', function (Blueprint $table) {
			$table->dropColumn('is_justify_my_trip');
			$table->dropColumn('remarks');
		});
	}
}
