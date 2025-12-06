<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PoSupplierDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('po_supplier_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('po_supplier_id')->unsigned()->nullable();
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable();
            $table->bigInteger('product_recipe_unit_id')->unsigned()->nullable();
            $table->bigInteger('brand_id')->unsigned()->nullable();
            $table->integer('qty')->nullable();
            $table->string('barcode')->nullable();
            $table->timestamps();
        });

        Schema::table('po_supplier_details', function (Blueprint $table) {
            $table->foreign('po_supplier_id')
                        ->references('id')->on('po_suppliers')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_ingredient_id')
                        ->references('id')->on('product_ingredients')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_recipe_unit_id')
                        ->references('id')->on('product_recipe_units')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('brand_id')
                        ->references('id')->on('brands')
                        ->onUpdate('cascade')
                        ->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('po_supplier_details');
    }
}
