<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnBrowniesWarehouseTable extends Migration
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
    protected $table = 'brownies_target_plan_warehouses';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->string('barcode_po')->nullable()->after('nomor_po');
            $table->bigInteger('po_order_product_id')->unsigned()->nullable()->after('date')->index();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('po_order_product_id')
                        ->references('id')->on('po_order_products')
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
            $table->dropForeign(['po_order_product_id']);
            $table->dropColumn(['barcode_po', 'po_order_product_id']);
        });
    }
}
