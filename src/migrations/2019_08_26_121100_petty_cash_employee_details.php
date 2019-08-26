<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PettyCashEmployeeDetails extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('petty_cash_employee_details', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('petty_cash_id');
			$table->unsignedInteger('expence_type');
			$table->date('date');
			$table->unsignedInteger('purpose_id')->nullable();
			$table->unsignedInteger('travel_mode_id')->nullable();
			$table->string('from_place', 191)->nullable();
			$table->string('to_place', 191)->nullable();
			$table->unsignedDecimal('from_km')->nullable();
			$table->unsignedDecimal('to_km')->nullable();
			$table->unsignedDecimal('amount', 10, 2)->nullable();
			$table->unsignedDecimal('tax', 10, 2)->nullable();
			$table->string('remarks', 191);
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->foreign('petty_cash_id')->references('id')->on('petty_cash')->onDelete('cascade')->onUpdate('cascade');
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
		Schema::dropIfExists('petty_cash_employee_details');

	}
}
