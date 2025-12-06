<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductIngredientUnits extends Migration
{
    /**
     * DB Conenction
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'product_ingredient_units';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
        //     $table->bigInteger('product_ingredient_id')->unsigned()->nullable()->index();
        //     $table->bigInteger('product_recipe_unit_id')->unsigned()->nullable()->index();
        //     $table->timestamps();
        // });

        // Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            // $table->foreign('product_ingredient_id')
            //             ->references('id')->on('product_ingredients')
            //             ->onUpdate('cascade')
            //             ->onDelete('cascade');
        //     $table->foreign('product_recipe_unit_id')
        //                 ->references('id')->on('product_recipe_units')
        //                 ->onUpdate('cascade')
        //                 ->onDelete('cascade');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::connection($this->connection)->dropIfExists($this->table);
    }
}
