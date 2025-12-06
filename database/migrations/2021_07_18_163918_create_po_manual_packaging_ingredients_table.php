<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoManualPackagingIngredientsTable extends Migration
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
    protected $table = 'po_manual_packaging_details';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->bigInteger('po_manual_packaging_id')->unsigned()->nullable()->index('pom_packaging_id_index');
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable()->index('po_manual_packaging_ingredient_id_index');
            $table->bigInteger('product_id')->unsigned()->nullable()->index('po_manual_packaging_product_id_index');
            $table->integer('qty')->nullable()->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('po_manual_packaging_id', 'pom_packaging_id_foreign')
                        ->references('id')->on('po_manual_packagings')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_ingredient_id', 'ingredient_id_manual_foreign')
                        ->references('id')->on('product_ingredients')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_id', 'product_id_manual_foreign')
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
