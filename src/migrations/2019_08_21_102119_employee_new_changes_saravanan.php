<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EmployeeNewChangesSaravanan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('designation_id')->nullable()->after('grade_id');
            $table->date('date_of_joining')->nullable()->after('designation_id');
            $table->string('aadhar_no', 191)->nullable()->after('date_of_joining');
            $table->string('pan_no', 191)->nullable()->after('aadhar_no');
             $table->foreign('designation_id')->references('id')->on('designations')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign('employees_designation_id_foreign');
            $table->dropColumn('designation_id');
            $table->dropColumn('date_of_joining');
            $table->dropColumn('aadhar_no');
            $table->dropColumn('pan_no');
        });
    }
}
