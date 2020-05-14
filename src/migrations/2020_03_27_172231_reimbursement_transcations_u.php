<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ReimbursementTranscationsU extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('reimbursement_transcations', function (Blueprint $table) {
            $table->dropForeign('reimbursement_transcations_petty_cash_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reimbursement_transcations', function (Blueprint $table) {
            $table->foreign('petty_cash_id')->references('id')->on('petty_cash')->onDelete('cascade')->onUpdate('cascade');
        });
    }
}
