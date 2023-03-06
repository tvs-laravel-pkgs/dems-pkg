<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CHondaAxaptaExports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('honda_axapta_exports', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedInteger('entity_type_id')->nullable();
            $table->integer('entity_id');
            $table->string('brcd', 150)->nullable();
            $table->string('doc_type', 150)->nullable();
            $table->string('doc_no', 250)->nullable();
            $table->date('doc_date')->nullable();
            $table->string('account', 250)->nullable();
            $table->string('acc_type', 150)->nullable();
            $table->text('tran_txt')->nullable();
            $table->unsignedDecimal('dbt_amt', 12,2)->nullable();
            $table->unsignedDecimal('crd_amt', 12,2)->nullable();
            $table->string('dept', 100)->nullable();
            $table->string('cost_center', 150)->nullable();
            $table->string('employee', 150)->nullable();
            $table->string('vin', 150)->nullable();
            $table->string('ro', 150)->nullable();
            $table->string('purpose', 150)->nullable();
            $table->string('budget_code', 150)->nullable();
            $table->string('sup_inv_no', 250)->nullable();
            $table->date('sup_inv_date')->nullable();
            $table->string('ref_no',250)->nullable();
            $table->date('ref_date')->nullable();
            $table->string('commcd', 150)->nullable();
            $table->string('vatper', 150)->nullable();
            $table->string('company', 150)->nullable();
            $table->string('intercobr', 150)->nullable();
            $table->string('interco', 150)->nullable();
            $table->unsignedDecimal('cost_price', 12,2)->nullable();
            $table->string('hsn_code', 150)->nullable();
            $table->string('ship_to_add', 150)->nullable();
            $table->string('ship_state', 150)->nullable();
            $table->string('unreg_flag', 150)->nullable();
            $table->string('reversechrgind', 150)->nullable();
            $table->string('cn_reason', 150)->nullable();
            $table->string('lr_no', 250)->nullable();
            $table->date('lr_date')->nullable();
            $table->string('ls_os', 150)->nullable();
            $table->string('grn_no', 250)->nullable();
            $table->date('grn_date')->nullable();
            $table->unsignedInteger('qty')->nullable();
            $table->string('gstin', 150)->nullable();
            $table->string('paymemt_ref', 150)->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('SET NULL')->onUpdate('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('SET NULL')->onUpdate('cascade');
            $table->foreign('entity_type_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('honda_axapta_exports');
    }
}
