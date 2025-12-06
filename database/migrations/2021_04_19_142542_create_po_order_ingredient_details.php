<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoOrderIngredientDetails extends Migration
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
    protected $table = 'po_order_ingredient_details';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('po_order_ingredient_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_ingredient_unit_id')->unsigned()->nullable()->index();
            $table->double('qty')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('po_order_ingredient_id')
                        ->references('id')->on('po_order_ingredients')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_ingredient_id')
                        ->references('id')->on('product_ingredients')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('product_ingredient_unit_id')
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
        Schema::connection($this->connection)->dropIfExists($this->table);
    }
}
