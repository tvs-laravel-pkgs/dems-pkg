<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsSharedClaimOkInLodgingShareDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('lodging_share_details', 'is_shared_claim_ok')) {
            Schema::table('lodging_share_details', function (Blueprint $table) {
                $table->unsignedTinyInteger('is_shared_claim_ok')->default(0)->after('employee_id')->comment('1-Yes, 0-No');
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
        if (Schema::hasColumn('lodging_share_details', 'is_shared_claim_ok')) {
            Schema::table('lodging_share_details', function (Blueprint $table) {
                $table->dropColumn('is_shared_claim_ok');
            });
        }
    }
}
