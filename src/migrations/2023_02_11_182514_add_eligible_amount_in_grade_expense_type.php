<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEligibleAmountInGradeExpenseType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('grade_expense_type')) {
            Schema::table('grade_expense_type', function (Blueprint $table) {
                if (!Schema::hasColumn('grade_expense_type', 'less_than_240')) {
                    $table->decimal('less_than_240', 6, 2)->nullable()->after('eligible_amount');
                }
                if (!Schema::hasColumn('grade_expense_type', 'less_than_480')) {
                    $table->decimal('less_than_480', 6, 2)->nullable()->after('less_than_240');
                }
                if (!Schema::hasColumn('grade_expense_type', 'less_than_1440')) {
                    $table->decimal('less_than_1440', 6, 2)->nullable()->after('less_than_480');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('grade_expense_type')) {
            Schema::table('grade_expense_type', function (Blueprint $table) {
                if (Schema::hasColumn('grade_expense_type', 'less_than_1440')) {
                    $table->dropColumn('less_than_1440');
                }
                if (Schema::hasColumn('grade_expense_type', 'less_than_480')) {
                    $table->dropColumn('less_than_480');
                }
                if (Schema::hasColumn('grade_expense_type', 'less_than_240')) {
                    $table->dropColumn('less_than_240');
                }
            });
        }
    }
}
