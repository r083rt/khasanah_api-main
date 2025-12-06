<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductStockLogTempsTables extends Migration
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'product_stock_log_temps';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->bigInteger('branch_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_id')->unsigned()->nullable()->index();
            $table->integer('stock')->nullable();
            $table->string('from')->nullable();
            $table->string('table_reference')->nullable();
            $table->bigInteger('table_id')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('branch_id')
                        ->references('id')->on('branches')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_id')
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
