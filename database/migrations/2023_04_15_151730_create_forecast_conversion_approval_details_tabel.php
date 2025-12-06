<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForecastConversionApprovalDetailsTabel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forecast_conversion_approval_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('forecast_conversion_approval_id')->unsigned()->nullable();
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable();
            $table->double('conversion')->nullable();
            $table->double('conversion_2')->nullable();
            $table->bigInteger('conversion_unit_id')->unsigned()->nullable();
            $table->double('conversion_rounding')->nullable();
            $table->bigInteger('conversion_rounding_unit_id')->unsigned()->nullable();
            $table->double('conversion_latest')->nullable();
            $table->double('conversion_latest_rounding')->nullable();
            $table->bigInteger('conversion_latest_rounding_unit_id')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::table('forecast_conversion_approval_details', function (Blueprint $table) {
            $table->foreign('forecast_conversion_approval_id', 'fcad_fca_id_foreign')
                        ->references('id')->on('forecast_conversion_approvals')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('product_ingredient_id', 'fcad_fi_id_foreign')
                        ->references('id')->on('product_ingredients')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('conversion_unit_id', 'fcad_cu_id_foreign')
                        ->references('id')->on('product_recipe_units')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('conversion_rounding_unit_id', 'fcad_cru_id_foreign')
                        ->references('id')->on('product_recipe_units')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('conversion_latest_rounding_unit_id', 'fcad_clru_id_foreign')
                        ->references('id')->on('product_recipe_units')
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
        Schema::dropIfExists('forecast_conversion_approval_details');
    }
}
