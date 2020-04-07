<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CompanyBudgetC extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_budget', function (Blueprint $table) {
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('financial_year_id');
            $table->decimal('outstation_budget_amount', 16,2)->unsigned();
            $table->decimal('local_budget_amount', 16,2)->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('financial_year_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('company_budget');
    }
}
