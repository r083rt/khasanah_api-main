<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterPackagingRecipesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_packaging_recipes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('master_packaging_id')->unsigned()->nullable();
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable();
            // $table->bigInteger('product_ingredient_recipe_unit_id')->unsigned()->nullable();
            $table->double('measure')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::table('master_packaging_recipes', function (Blueprint $table) {
            $table->foreign('master_packaging_id')
                        ->references('id')->on('master_packagings')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_ingredient_id')
                        ->references('id')->on('product_ingredients')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            // $table->foreign('product_ingredient_recipe_unit_id', 'master_packaging_recipes_pirui')
            //             ->references('id')->on('product_recipe_units')
            //             ->onUpdate('cascade')
            //             ->onDelete('set null');
            $table->foreign('created_by')
                        ->references('id')->on('users')
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
        Schema::dropIfExists('master_packaging_recipes');
    }
}
