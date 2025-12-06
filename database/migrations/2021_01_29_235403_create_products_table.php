<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
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
    protected $table = 'products';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->bigInteger('product_category_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_unit_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_unit_delivery_id')->unsigned()->nullable()->index();
            $table->integer('unit_value')->nullable();
            $table->integer('price')->nullable();
            $table->integer('price_sale')->nullable();
            $table->integer('gramasi')->nullable();
            $table->integer('mill_barrel')->nullable();
            $table->integer('shop_roller')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('product_category_id')
                        ->references('id')->on('product_categories')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('product_unit_id')
                        ->references('id')->on('product_units')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('product_unit_delivery_id')
                        ->references('id')->on('product_units')
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
