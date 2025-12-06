<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnPoSupplierDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('return_po_supplier_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('return_id')->unsigned()->nullable();
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable();
            $table->bigInteger('product_recipe_unit_id')->unsigned()->nullable();
            $table->bigInteger('brand_id')->unsigned()->nullable();
            $table->integer('qty')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('real_price')->nullable();
            $table->integer('price')->nullable();
            $table->integer('discount')->nullable();
            $table->timestamps();
        });

        Schema::table('return_po_supplier_details', function (Blueprint $table) {
            $table->foreign('return_id')
                        ->references('id')->on('return_po_suppliers')
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
        Schema::dropIfExists('return_po_supplier_details');
    }
}
