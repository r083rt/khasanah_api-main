<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSettingPoSuplier extends Migration
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
            $table->dropForeign(['purchasing_supplier_id']);
            $table->dropColumn(['purchasing_supplier_id']);
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
            $table->bigInteger('purchasing_supplier_id')->unsigned()->nullable()->index();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('purchasing_supplier_id')
                        ->references('id')->on('purchasing_suppliers')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
        });
    }
}
