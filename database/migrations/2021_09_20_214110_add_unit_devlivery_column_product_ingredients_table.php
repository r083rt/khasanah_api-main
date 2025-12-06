<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitDevliveryColumnProductIngredientsTable extends Migration
{
    /**
     * DB Connection
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'product_ingredients';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->bigInteger('product_ingredient_unit_delivery_id')->unsigned()->nullable()->index()->after('hpp');
            $table->integer('unit_value')->nullable()->index()->after('product_ingredient_unit_delivery_id');
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('product_ingredient_unit_delivery_id')
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
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->dropForeign(['product_ingredient_unit_delivery_id']);
            $table->dropColumn(['product_ingredient_unit_delivery_id', 'unit_value']);
        });
    }
}
