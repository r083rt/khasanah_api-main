<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePoSupplierDetailsTableQtyReturned extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('po_supplier_details', function (Blueprint $table) {
            $table->integer('qty_returned')->nullable()->after('qty_received');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('po_supplier_details', function (Blueprint $table) {
            //
        });
    }
}
