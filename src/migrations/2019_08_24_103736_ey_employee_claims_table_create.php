<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EyEmployeeClaimsTableCreate extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('ey_employee_claims', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('employee_id');
			$table->unsignedInteger('trip_id');
			$table->unsignedDecimal('total_amount');
			$table->unsignedInteger('status_id');
			$table->unsignedInteger('payment_id')->nullable();
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade')->onUpdate('cascade');
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
		Schema::dropIfExists('ey_employee_claims');
	}
}
