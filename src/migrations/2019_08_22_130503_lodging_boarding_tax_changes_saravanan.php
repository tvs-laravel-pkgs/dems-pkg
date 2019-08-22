<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LodgingBoardingTaxChangesSaravanan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('boardings', function (Blueprint $table) {
            $table->decimal('tax', 5, 2)->nullable()->after('amount');
        });
        Schema::table('local_travels', function (Blueprint $table) {
            $table->decimal('tax', 5, 2)->nullable()->after('amount');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('boardings', function (Blueprint $table) {
           $table->dropColumn('tax');
        });
        Schema::table('local_travels', function (Blueprint $table) {
            $table->dropColumn('tax');
        });
    }
}
