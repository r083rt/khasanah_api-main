<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ForecastConversionDetailLogsTable extends Migration
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
    protected $table = 'forecast_conversion_detail_logs';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('forecast_conversion_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable()->index();
            $table->bigInteger('master_packaging_id')->unsigned()->nullable()->index();
            $table->integer('qty')->nullable();
            $table->double('measure')->nullable();
            $table->double('qty_measure')->nullable();
            $table->integer('gramasi_production')->nullable();
            $table->double('qty_packaging')->nullable();
            $table->double('measure_packaging')->nullable();
            $table->double('conversion')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('forecast_conversion_id')
                        ->references('id')->on('forecast_conversions')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_ingredient_id')
                        ->references('id')->on('product_ingredients')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_id')
                        ->references('id')->on('products')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('master_packaging_id')
                        ->references('id')->on('master_packagings')
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
