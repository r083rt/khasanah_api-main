<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingPoSuppliers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forecast_conversion_setting_po_suppliers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('forecast_conversion_setting_po_id')->unsigned()->nullable();
            $table->bigInteger('purchasing_supplier_id')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::table('forecast_conversion_setting_po_suppliers', function (Blueprint $table) {
            $table->foreign('purchasing_supplier_id', 'fcspos_psi_foreign')
                        ->references('id')->on('purchasing_suppliers')
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
        Schema::dropIfExists('forecast_conversion_setting_po_suppliers');
    }
}
