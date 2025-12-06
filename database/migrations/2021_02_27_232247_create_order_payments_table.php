<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPaymentsTable extends Migration
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
    protected $table = 'order_payments';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id')->unsigned()->nullable()->index();
            $table->integer('payment_number')->nullable();
            $table->bigInteger('payment_id')->unsigned()->nullable()->index();
            $table->string('payment_name')->nullable();
            $table->string('payment_desc')->nullable();
            $table->enum('type', ['repayment-today', 'order-payment-taken', 'dp', 'paid'])->nullable();
            $table->integer('total_price')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('order_id')
                        ->references('id')->on('orders')
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
        Schema::connection($this->connection)->dropIfExists($this->table);
    }
}
