<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Trip extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('payments', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('payment_of_id');
			$table->unsignedInteger('entity_id');
			$table->string('reference_number', 191);
			$table->date('date');
			$table->unsignedDecimal('amount');
			$table->unsignedInteger('payment_mode_id');
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('payment_of_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('payment_mode_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["payment_of_id", "entity_id", "reference_number"]);
		});

		Schema::create('trips', function (Blueprint $table) {
			$table->increments('id');
			$table->string('number', 191);
			$table->unsignedInteger('employee_id');
			$table->unsignedInteger('purpose_id');
			$table->string('description', 191)->nullable();
			$table->unsignedInteger('manager_id');
			$table->unsignedInteger('status_id');
			$table->unsignedDecimal('advance_received', 10, 2);
			$table->unsignedDecimal('claim_amount', 10, 2)->nullable();
			$table->datetime('claimed_date')->nullable();
			$table->unsignedInteger('payment_id')->nullable();
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('purpose_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('manager_id')->references('id')->on('employees')->onDelete('cascade')->onUpdate('cascade');
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
		Schema::dropIfExists('trips');
		Schema::dropIfExists('payments');
	}
}
