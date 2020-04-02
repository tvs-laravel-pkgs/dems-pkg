<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OutletBudgetU extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_budget', function (Blueprint $table) {
            $table->dropColumn('amount');
            $table->decimal('outstation_budget_amount', 16,2)->after('sbu_id');
            $table->decimal('local_budget_amount', 16,2)->after('outstation_budget_amount');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_budget', function (Blueprint $table) {
            $table->dropColumn('outstation_budget_amount');
            $table->dropColumn('local_budget_amount');
            $table->decimal('amount', 16,2)->after('sbu_id');
        });
    }
}
