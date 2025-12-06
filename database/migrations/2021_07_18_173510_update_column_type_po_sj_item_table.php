<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateColumnTypePoSjItemTable extends Migration
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
    protected $table = 'po_sj_items';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection($this->connection)->statement("ALTER TABLE $this->table CHANGE `type` `type` ENUM('po_order_product','po_order_ingredient','po_manual_product','po_manual_ingredient','po_central_product','po_central_ingredient');");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection($this->connection)->statement("ALTER TABLE $this->table CHANGE `type` `type` ENUM('po_order_product','po_order_ingredient','po_request_product','po_request_ingredient','po_central_product','po_central_ingredient');");
    }
}
