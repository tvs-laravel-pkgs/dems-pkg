<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableLocalTrips extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('local_trips', function (Blueprint $table) {
			$table->unsignedInteger('beta_amount')->nullable()->after('end_date');
			$table->unsignedInteger('other_amount')->nullable()->after('beta_amount');
			$table->tinyInteger('is_justify_my_trip')->nullable()->comment('0 - No, 1 - Yes')->after('payment_id');
			$table->string('remarks', 255)->nullable()->after('is_justify_my_trip');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('local_trips', function (Blueprint $table) {
			$table->dropColumn('beta_amount');
			$table->dropColumn('other_amount');
			$table->dropColumn('is_justify_my_trip');
			$table->dropColumn('remarks');
		});
	}
}
