<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * DB Conenction
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('invoice')->nullable();
            $table->bigInteger('product_category_id')->unsigned()->nullable()->index();
            $table->bigInteger('branch_id')->unsigned()->nullable()->index();
            $table->bigInteger('customer_id')->unsigned()->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->bigInteger('payment_id')->unsigned()->nullable()->index();
            $table->string('payment_name')->nullable();
            $table->string('payment_desc')->nullable();
            $table->integer('total_price')->nullable();
            $table->integer('pay')->nullable();
            $table->enum('payment_type', ['paid', 'dp', 'pay-later'])->nullable()->default('paid');
            $table->enum('type', ['cashier', 'order'])->nullable()->default('cashier');
            $table->text('note')->nullable();
            $table->date('date_pickup')->nullable();
            $table->dateTime('received_date')->nullable();
            $table->bigInteger('received_by')->unsigned()->nullable()->index();
            $table->enum('status_payment', ['paid', 'not-paid'])->nullable()->default('paid');
            $table->enum('status_pickup', ['new', 'done'])->nullable()->default('new');
            $table->enum('status', ['new', 'canceled', 'completed'])->nullable()->default('new');
            $table->enum('discount_type', ['branch', 'customer'])->nullable();
            $table->dateTime('refund_dp_date')->nullable();
            $table->bigInteger('refund_by')->unsigned()->nullable()->index();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists($this->table);
    }
}
