<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGradeAdvanceEligibility extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('grade_advanced_eligibility', function (Blueprint $table) {
			$table->increments('id')->first();
			$table->unsignedDecimal('stay_type_disc', 10, 2)->nullable()->after('advanced_eligibility');
			$table->boolean('deviation_eligiblity')->default(2)->comment('1-Yes, 2-No')->after('stay_type_disc');
		});
	}

	/**
	 * Reverse the migrations.s
	 *
	 * @return void
	 */
	public function down() {
		//
	}
}
