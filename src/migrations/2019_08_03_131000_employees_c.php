<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EmployeesC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('employees', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->string('code', 191);
			$table->unsignedInteger('outlet_id');
			$table->unsignedInteger('reporting_to_id')->nullable();
			$table->unsignedInteger('grade_id');
			$table->unsignedInteger('created_by');
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->foreign('grade_id')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
		});

		Schema::table('employees', function (Blueprint $table) {
			$table->foreign('reporting_to_id')->references('id')->on('employees')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["company_id", "code"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		// Schema::table('employees', function (Blueprint $table) {
		// 	// $table->dropForeign('employees_company_id_foreign');

		// 	// $table->dropForeign('employees_outlet_id_foreign');
		// 	// $table->dropForeign('employees_reporting_to_id_foreign');
		// 	// $table->dropForeign('employees_grade_id_foreign');
		// 	// $table->dropForeign('employees_created_by_foreign');
		// 	// $table->dropForeign('employees_updated_by_foreign');
		// 	// $table->dropForeign('employees_deleted_by_foreign');

		// 	// $table->dropUnique('employees_company_id_code_unique');
		// });

		Schema::dropIfExists('employees');
	}
}
