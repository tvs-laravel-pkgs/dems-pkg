<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableImportJobs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('import_jobs', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->unsignedInteger('type_id')->default(3361);
			$table->unsignedInteger('total_records');
			$table->unsignedInteger('processed');
			$table->unsignedInteger('remaining');
			$table->unsignedInteger('new');
			$table->unsignedInteger('updated');
			$table->unsignedInteger('error');
			$table->unsignedInteger('status_id');
			$table->string('src_file', 255);
			$table->string('export_file', 255)->nullable();
			$table->longText('server_status')->nullable();
			$table->unsignedInteger('created_by')->nullable();
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->timestamps();
			$table->softdeletes();
		});
	}

	/**
	 * Reverse the migrations.s
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('import_jobs');
	}
}
