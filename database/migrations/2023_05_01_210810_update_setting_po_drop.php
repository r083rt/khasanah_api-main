<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSettingPoDrop extends Migration
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
    protected $table = 'forecast_conversion_setting_po';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->dropColumn(['date_1', 'qty_1', 'date_2', 'qty_2', 'date_3', 'qty_3']);
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
            $table->tinyInteger('date_1')->nullable();
            $table->integer('qty_1')->nullable()->index();
            $table->tinyInteger('date_2')->nullable();
            $table->integer('qty_2')->nullable()->index();
            $table->tinyInteger('date_3')->nullable();
            $table->integer('qty_3')->nullable()->index();
        });
    }
}
