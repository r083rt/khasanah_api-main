<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForecastConversionApprovalDetailBranchesTabel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forecast_conversion_approval_detail_branches', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('forecast_conversion_approval_id')->unsigned()->nullable();
            $table->bigInteger('branch_id')->unsigned()->nullable();
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable();
            $table->double('qty_real')->nullable();
            $table->double('qty_total')->nullable();
            $table->double('buffer')->nullable();
            $table->timestamps();
        });

        Schema::table('forecast_conversion_approval_detail_branches', function (Blueprint $table) {
            $table->foreign('forecast_conversion_approval_id', 'fcadb_fca_id_foreign')
                        ->references('id')->on('forecast_conversion_approvals')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('branch_id', 'fcadb_branch_id_foreign')
                        ->references('id')->on('branches')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('product_ingredient_id', 'fcadb_fi_id_foreign')
                        ->references('id')->on('product_ingredients')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('forecast_conversion_approval_detail_branches');
    }
}
