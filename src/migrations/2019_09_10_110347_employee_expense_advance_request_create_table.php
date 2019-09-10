<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EmployeeExpenseAdvanceRequestCreateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('expense_voucher_advance_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->date('date');
            $table->unsignedDecimal('advance_amount');
            $table->Integer('balance_amount');
            $table->unsignedInteger('status_id');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

        });
        Schema::table('petty_cash', function (Blueprint $table) {
             $table->unsignedInteger('expense_voucher_id')->nullable()->after('employee_id');
             $table->foreign('expense_voucher_id')->references('id')->on('expense_voucher_advance_requests')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::dropIfExists('expense_voucher_advance_requests');
        Schema::table('petty_cash', function (Blueprint $table) {
            $table->dropForeign('petty_cash_expense_voucher_id_foreign');
            $table->dropColumn('expense_voucher_id');
        });
    }
}
