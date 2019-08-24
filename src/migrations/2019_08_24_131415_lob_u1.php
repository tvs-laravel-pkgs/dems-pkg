<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LobU1 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('lobs', function (Blueprint $table) {
			$table->string('name', 191)->change();

			$table->unsignedInteger('company_id')->default(1)->after('id');
			$table->unsignedInteger('created_by')->nullable()->after('name');
			$table->unsignedInteger('updated_by')->nullable()->after('created_by');
			$table->unsignedInteger('deleted_by')->nullable()->after('updated_by');
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');

			$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["company_id", "name"]);
		});

		Schema::table('sbus', function (Blueprint $table) {
			$table->string('name', 191)->change();

			$table->foreign('lob_id')->references('id')->on('lobs')->onDelete('cascade')->onUpdate('cascade');
			$table->unsignedInteger('created_by')->nullable()->after('name');
			$table->unsignedInteger('updated_by')->nullable()->after('created_by');
			$table->unsignedInteger('deleted_by')->nullable()->after('updated_by');
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["lob_id", "name"]);

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('lobs', function (Blueprint $table) {
			$table->dropForeign('lobs_company_id_foreign');
			$table->dropForeign('lobs_created_by_foreign');
			$table->dropForeign('lobs_updated_by_foreign');
			$table->dropForeign('lobs_deleted_by_foreign');

			$table->dropUnique('lobs_company_id_name_unique');

			$table->dropColumn('company_id');

			$table->dropColumn('created_by');
			$table->dropColumn('updated_by');
			$table->dropColumn('deleted_by');

			$table->dropColumn('created_at');
			$table->dropColumn('updated_at');
			$table->dropColumn('deleted_at');

		});

		Schema::table('sbus', function (Blueprint $table) {

			$table->dropForeign('sbus_lob_id_foreign');
			$table->dropForeign('sbus_created_by_foreign');
			$table->dropForeign('sbus_updated_by_foreign');
			$table->dropForeign('sbus_deleted_by_foreign');

			$table->dropUnique('sbus_lob_id_name_unique');

			$table->dropColumn('created_by');
			$table->dropColumn('updated_by');
			$table->dropColumn('deleted_by');

			$table->dropColumn('created_at');
			$table->dropColumn('updated_at');
			$table->dropColumn('deleted_at');

		});

	}
}
