<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrowniesStoreProductionsTable extends Migration
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
    protected $table = 'brownies_store_productions';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id')->unsigned()->nullable()->index();
            $table->bigInteger('master_packaging_id')->unsigned()->nullable()->index();
            $table->bigInteger('branch_id')->unsigned()->nullable()->index();
            $table->date('date')->nullable();
            $table->string('product_code')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('total_po')->nullable();
            $table->integer('grind')->nullable();
            $table->integer('pcs')->nullable();
            $table->integer('recipe_production')->nullable();
            $table->json('product_ids')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('product_id')
                        ->references('id')->on('products')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('master_packaging_id')
                        ->references('id')->on('master_packagings')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('branch_id')
                        ->references('id')->on('branches')
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
