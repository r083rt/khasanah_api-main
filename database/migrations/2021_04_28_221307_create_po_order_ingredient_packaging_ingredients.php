<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoOrderIngredientPackagingIngredients extends Migration
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
    protected $table = 'po_order_ingredient_packaging_ingredients';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->bigInteger('po_order_ingredient_packaging_id')->unsigned()->nullable()->index('pooi_packaging_id_index');
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable()->index('pooi_packaging_ingredient_id_index');
            $table->integer('qty')->nullable()->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('po_order_ingredient_packaging_id', 'poi_packaging_id_foreign')
                        ->references('id')->on('po_order_ingredient_packagings')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_ingredient_id', 'ingredient_id_poipi_foreign')
                        ->references('id')->on('product_ingredients')
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
        Schema::connection($this->connection)->dropIfExists($this->table);
    }
}
