<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockOpnameIngredientDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_opname_ingredient_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('stock_opname_ingredient_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_recipe_unit_id')->unsigned()->nullable()->index();
            $table->integer('stock_system')->nullable();
            $table->integer('stock_real')->nullable();
            $table->integer('stock_difference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::table('stock_opname_ingredient_details', function (Blueprint $table) {
            $table->foreign('stock_opname_ingredient_id', 'soid_sopi')
                        ->references('id')->on('stock_opname_ingredients')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_recipe_unit_id')
                        ->references('id')->on('product_recipe_units')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_opname_ingredient_details');
    }
}
