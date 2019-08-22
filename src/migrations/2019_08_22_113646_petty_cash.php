<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PettyCash extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('petty_cash', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('petty_cash_employee_details_id');
			$table->foreign('petty_cash_employee_details_id')->references('id')->on('petty_cash_employee_details')->onDelete('cascade')->onUpdate('cascade');
			$table->unsignedInteger('expence_type');
			$table->date('date');
			$table->unsignedInteger('purpose_id');
			$table->unsignedInteger('travel_mode_id');
			$table->string('from_place', 191);
			$table->string('to_place', 191);
			$table->unsignedDecimal('from_KM_reading');
			$table->unsignedDecimal('to_KM_reading');
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->foreign('expence_type')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('purpose_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('travel_mode_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('petty_cash');
	}
}
