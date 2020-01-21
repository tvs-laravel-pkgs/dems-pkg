<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterExpenseRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('expense_voucher_advance_requests', function (Blueprint $table) {
            $table->string('description',191)->nullable()->after('status_id');
            $table->Integer('balance_amount')->nullable()->change();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expense_voucher_advance_requests', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
}
