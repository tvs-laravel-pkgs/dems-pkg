<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CLodgingShareDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('lodgings', function (Blueprint $table) {
            $table->unsignedInteger('sharing_type_id')->nullable()->after('stay_type_id');
            $table->unsignedInteger('no_of_sharing')->nullable()->after('sharing_type_id');

            $table->foreign('sharing_type_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
        });

        Schema::create('lodging_share_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lodging_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('lodging_id')->references('id')->on('lodgings')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('SET NULL')->onUpdate('cascade');

            $table->unique(['lodging_id', 'employee_id'], 'lodging_share_unique');
            $table->index(['lodging_id', 'employee_id'], 'lodging_share_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lodging_share_details');
        Schema::table('lodgings', function (Blueprint $table) {
            $table->dropForeign('lodgings_sharing_type_id_foreign');
            $table->dropColumn('sharing_type_id');
            $table->dropColumn('no_of_sharing');
        });
    }
}
