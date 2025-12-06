<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductIngredientIdColumnTransferStockProductsTable extends Migration
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
    protected $table = 'transfer_stock_products';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable()->index()->after('product_id');
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('product_ingredient_id')
                        ->references('id')->on('product_ingredients')
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
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->dropForeign(['product_ingredient_id']);
            $table->dropColumn(['product_ingredient_id']);
        });
    }
}
