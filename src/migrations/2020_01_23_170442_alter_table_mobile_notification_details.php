<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableMobileNotificationDetails extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('mobile_notification_details', function (Blueprint $table) {
			$table->unsignedInteger('status_id')->nullable()->after('message');
			$table->tinyInteger('trip_type')->default(0)->comment('1-Local Trip, 0-Outstation Trip')->after('status_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		//
	}
}
