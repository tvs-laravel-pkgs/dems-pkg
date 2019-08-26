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
			$table->unsignedInteger('employee_id');
			$table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade')->onUpdate('cascade');
			$table->unsignedDecimal('total', 10, 2);
			$table->unsignedInteger('rejection_id')->nullable();
			$table->foreign('rejection_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->string('remarks', 191)->nullable();
			$table->unsignedInteger('status_id');
			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->date('date');
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();
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
