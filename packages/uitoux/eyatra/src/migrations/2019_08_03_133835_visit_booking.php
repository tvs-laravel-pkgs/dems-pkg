<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VisitBooking extends Migration {
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
			$table->unsignedInteger('created_by')->nullable();
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

		Schema::create('visit_booking', function (Blueprint $table) {
			$table->unsignedInteger('visit_id');
			$table->unsignedInteger('type_id');
			$table->unsignedInteger('travel_mode_id');
			$table->string('reference_number', 191);
			$table->unsignedDecimal('amount');
			$table->unsignedDecimal('tax');
			$table->unsignedDecimal('service_charge');
			$table->unsignedDecimal('total');
			$table->unsignedDecimal('claim_amount');
			$table->unsignedInteger('payment_status_id');
			$table->unsignedInteger('payment_id')->nullable();

			$table->foreign('visit_id')->references('id')->on('visits')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('type_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('travel_mode_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('payment_status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["visit_id", "type_id", "travel_mode_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('visit_booking');
		Schema::dropIfExists('payments');
	}
}
