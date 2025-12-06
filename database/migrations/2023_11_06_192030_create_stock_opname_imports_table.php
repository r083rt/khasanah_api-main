<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockOpnameImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_opname_imports', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('branch_id');
            $table->integer('week')->nullable();
            $table->integer('month')->nullable();
            $table->boolean('is_last_stock')->nullable();
            $table->integer('product_ingredient_id')->nullable();
            $table->string('product_ingredient_name')->nullable();
            $table->integer('product_recipe_unit_id_1')->nullable();
            $table->string('product_recipe_unit_1_name')->nullable();
            $table->integer('product_recipe_unit_id_2')->nullable();
            $table->string('product_recipe_unit_2_name')->nullable();
            $table->integer('product_recipe_unit_id_3')->nullable();
            $table->string('product_recipe_unit_3_name')->nullable();
            $table->integer('stock_1')->nullable();
            $table->integer('stock_2')->nullable();
            $table->integer('stock_3')->nullable();
            $table->boolean('is_valid')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_opname_imports');
    }
}
