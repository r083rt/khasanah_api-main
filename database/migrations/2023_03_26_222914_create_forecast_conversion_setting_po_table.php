<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForecastConversionSettingPoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forecast_conversion_setting_po', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('forecast_conversion_id')->unsigned()->nullable()->index();
            $table->bigInteger('purchasing_supplier_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable()->index();
            $table->bigInteger('brand_id')->unsigned()->nullable()->index();
            $table->string('barcode')->nullable();
            $table->integer('qty_total')->nullable();
            $table->bigInteger('product_recipe_unit_id')->unsigned()->nullable()->index();
            $table->tinyInteger('date_1')->nullable();
            $table->integer('qty_1')->nullable()->index();
            $table->tinyInteger('date_2')->nullable();
            $table->integer('qty_2')->nullable()->index();
            $table->tinyInteger('date_3')->nullable();
            $table->integer('qty_3')->nullable()->index();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
        });

        Schema::table('forecast_conversion_setting_po', function (Blueprint $table) {
            $table->foreign('forecast_conversion_id')
                        ->references('id')->on('forecast_conversions')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_ingredient_id')
                        ->references('id')->on('product_ingredients')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('purchasing_supplier_id')
                        ->references('id')->on('purchasing_suppliers')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_recipe_unit_id')
                        ->references('id')->on('product_recipe_units')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('created_by')
                        ->references('id')->on('users')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('brand_id')
                        ->references('id')->on('brands')
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
        Schema::dropIfExists('forecast_conversion_setting_po');
    }
}
