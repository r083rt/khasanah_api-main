<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingPoSupplierDeliveries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forecast_conversion_setting_po_supplier_deliveries', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('forecast_conversion_setting_po_supplier_id')->unsigned()->nullable();
            $table->enum('period', ['period_1', 'period_2', 'period_3'])->nullable();
            $table->date('date')->nullable();
            $table->tinyInteger('day')->nullable();
            $table->integer('qty')->nullable();
            $table->timestamps();
        });

        Schema::table('forecast_conversion_setting_po_supplier_deliveries', function (Blueprint $table) {
            $table->foreign('forecast_conversion_setting_po_supplier_id', 'fcsposd_fcspos_id_foreign')
                        ->references('id')->on('forecast_conversion_setting_po_suppliers')
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
        Schema::dropIfExists('forecast_conversion_setting_po_supplier_deliveries');
    }
}
