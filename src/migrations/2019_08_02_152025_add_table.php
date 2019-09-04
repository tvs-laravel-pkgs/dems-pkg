<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('countries', function (Blueprint $table) {
			$table->increments('id');
			$table->string('code', 2)->unique();
			$table->string('name', 191)->unique();
			$table->timestamps();
			$table->softdeletes();

		});
		Schema::create('companies', function (Blueprint $table) {
			$table->increments('id');
			$table->string('code', 10)->unique();
			$table->string('name', 191)->unique();
			$table->string('address', 250);
			$table->string('cin_number', 191)->nullable();
			$table->string('gst_number', 191)->nullable();
			$table->string('customer_care_email', 255);
			$table->string('customer_care_phone', 250);
			$table->string('reference_code', 10)->nullable();
			$table->unsignedInteger('created_by')->nullable();
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();

			$table->timestamps();
			$table->softdeletes();
		});

		Schema::create('config_types', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name', 191)->unique();
		});
		Schema::create('configs', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('config_type_id');
			$table->foreign('config_type_id')->references('id')->on('config_types')->onDelete('cascade')->onUpdate('cascade');
			$table->string('name', 191);
			$table->unique(['config_type_id', 'name']);
		});

		Schema::create('users', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->unsignedInteger('entity_type');
			$table->unsignedInteger('user_type_id')->nullable();
			$table->unsignedInteger('entity_id')->nullable();
			$table->string('username', 191);
			$table->string('name', 255)->nullable();
			$table->string('mobile_number', 10)->nullable();
			$table->string('password', 255);
			$table->string('email', 15)->nullable();
			$table->unsignedInteger('force_password_change')->nullable();
			$table->string('imei', 15)->nullable();
			$table->string('otp', 6)->nullable();
			$table->string('mpin', 10)->nullable();
			$table->string('profile_image', 255)->nullable();
			$table->string('remember_token', 255)->nullable();
			$table->dateTime('last_login')->nullable();
			$table->dateTime('last_logout')->nullable();
			$table->unsignedInteger('created_by')->nullable();
			$table->unsignedInteger('updated_by')->nullable();
			$table->unsignedInteger('deleted_by')->nullable();
			$table->softdeletes();
			$table->unique(['company_id', 'mobile_number']);
			$table->unique(['company_id', 'username']);
			$table->foreign('user_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
		Schema::table('companies', function (Blueprint $table) {
			$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		Schema::table('companies', function (Blueprint $table) {
			$table->dropForeign('companies_created_by_foreign');
			$table->dropForeign('companies_updated_by_foreign');
			$table->dropForeign('companies_deleted_by_foreign');
			$table->dropUnique('companies_code_unique');
			$table->dropUnique('companies_name_unique');
			$table->dropUnique('companies_cin_number_unique');
			$table->dropUnique('companies_gst_number_unique');

		});
		Schema::dropIfExists('companies');
		Schema::drop('config_types');

		Schema::table('configs', function (Blueprint $table) {
			$table->dropForeign('configs_config_type_id_foreign');
			$table->dropUnique('configs_config_type_id_name_unique');

		});
		Schema::dropIfExists('configs');
		Schema::table('users', function (Blueprint $table) {
			$table->dropForeign('users_company_id_foreign');
			$table->dropForeign('users_user_type_id_foreign');
			$table->dropForeign('users_created_by_foreign');
			$table->dropForeign('users_updated_by_foreign');
			$table->dropForeign('users_deleted_by_foreign');
			$table->dropUnique('users_company_id_username_unique');
			$table->dropUnique('users_company_id_mobile_number_unique');

		});
		Schema::dropIfExists('users');
		Schema::dropIfExists('countries');
		//
	}
}
