<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClosingProductsTable extends Migration
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
    protected $table = 'closing_products';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('closing_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_id')->unsigned()->nullable()->index();
            $table->string('product_name')->nullable();
            $table->string('product_code')->nullable();
            $table->integer('stock_system')->nullable();
            $table->integer('stock_real')->nullable();
            $table->integer('difference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('closing_id')
                        ->references('id')->on('closings')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_id')
                        ->references('id')->on('products')
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
