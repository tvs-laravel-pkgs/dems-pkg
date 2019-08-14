<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AgentClaimChanges extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('ey_agent_claims', function (Blueprint $table) {
			$table->unsignedDecimal('net_amount')->after('invoice_date')->default(0);
			$table->unsignedDecimal('tax')->after('net_amount')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('ey_agent_claims', function (Blueprint $table) {
			$table->dropColumn('net_amount');
			$table->dropColumn('tax');
		});
	}
}
