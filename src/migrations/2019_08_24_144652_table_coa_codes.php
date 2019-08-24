<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableCoaCodes extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('coa_codes', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('number');
			$table->string('account_description', 191);
			$table->unsignedInteger('account_types');
			$table->unsignedInteger('normal_balance');
			$table->string('description', 191);
			$table->unsignedInteger('final_statement');
			$table->unsignedInteger('group');
			$table->unsignedInteger('sub_group');
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->foreign('account_types')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('normal_balance')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('final_statement')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('group')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('sub_group')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
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
		Schema::dropIfExists('coa_codes');
	}
}
