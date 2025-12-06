<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForecastConversionSettingPoSupplierDeliveriesTableBranch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('forecast_conversion_setting_po_supplier_deliveries', function (Blueprint $table) {
            $table->string('branch')->nullable()->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('forecast_conversion_setting_po_supplier_deliveries', function (Blueprint $table) {
            //
        });
    }
}
