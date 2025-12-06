<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForecastConversionDetailsTabel extends Migration
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
    protected $table = 'forecast_conversion_details';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->double('conversion_latest')->nullable()->after('conversion_rounding_unit_id');
            $table->double('conversion_latest_rounding')->nullable()->after('conversion_latest');
            $table->bigInteger('conversion_latest_rounding_unit_id')->unsigned()->nullable()->after('conversion_latest_rounding');
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('conversion_latest_rounding_unit_id', 'forecast_conversion_details_clru_id_foreign')
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
            $table->dropForeign(['clru_id']);
            $table->dropColumn(['conversion_latest', 'conversion_latest_rounding', 'conversion_latest_rounding_unit_id']);
        });
    }
}
