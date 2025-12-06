<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoOrderProductPackagingProducts extends Migration
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
    protected $table = 'po_order_product_packaging_products';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->bigInteger('po_order_product_packaging_id')->unsigned()->nullable()->index('poop_packaging_id_index');
            $table->bigInteger('product_id')->unsigned()->nullable()->index();
            $table->integer('qty')->nullable()->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('po_order_product_packaging_id', 'pop_packaging_id_foreign')
                        ->references('id')->on('po_order_product_packagings')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_id', 'product_id_foreign')
                        ->references('id')->on('products')
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
