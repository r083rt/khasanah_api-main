<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTypeOrderPaymentsTable extends Migration
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
    protected $table = 'order_payments';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection($this->connection)->statement("ALTER TABLE $this->table CHANGE `type` `type` ENUM('repayment-today', 'order-payment-taken', 'dp', 'paid', 'payment-before-taken', 'payment-after-taken');");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection($this->connection)->statement("ALTER TABLE $this->table CHANGE `type` `type` ENUM('repayment-today', 'order-payment-taken', 'dp', 'paid');");
    }
}
