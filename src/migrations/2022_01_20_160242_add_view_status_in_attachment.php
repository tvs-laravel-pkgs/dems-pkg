<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddViewStatusInAttachment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('attachments', 'view_status')) {
            Schema::table('attachments', function (Blueprint $table) {
                $table->unsignedInteger('view_status')->after('name')->comment('0 -> No, 1 -> Yes')->default(0);
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
        if (Schema::hasColumn('attachments', 'view_status')) {
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropColumn('view_status');
            });
        }
    }
}
