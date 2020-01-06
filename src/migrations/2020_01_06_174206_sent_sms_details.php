<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SentSmsDetails extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('dems_sent_sms_details', function (Blueprint $table) {
			$table->increments('id');
			$table->string('employee_id', 20);
			$table->string('sender_id', 20);
			$table->string('sms_from', 30);
			$table->string('message', 512);
			$table->string('message_id', 32);
			$table->string('mobile_number', 16);
			$table->string('customer_id', 16);
			$table->timestamp('created_at');
			$table->timestamp('updated_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('dems_sent_sms_details');
	}
}
