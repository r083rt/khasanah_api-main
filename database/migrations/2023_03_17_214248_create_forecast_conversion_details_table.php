<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForecastConversionDetailsTable extends Migration
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
    protected $table = 'forecast_conversion_details';

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
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable()->index();
            $table->double('conversion')->nullable();
            $table->double('conversion_2')->nullable();
            $table->bigInteger('conversion_unit_id')->unsigned()->nullable()->index();
            $table->double('conversion_rounding')->nullable();
            $table->bigInteger('conversion_rounding_unit_id')->unsigned()->nullable()->index();
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
            $table->foreign('conversion_unit_id')
                        ->references('id')->on('product_recipe_units')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('conversion_rounding_unit_id')
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
        Schema::connection($this->connection)->dropIfExists($this->table);
    }
}
