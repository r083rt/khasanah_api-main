<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClosingDetailLogsTable extends Migration
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
    protected $table = 'closing_detail_references';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('closing_id')->unsigned()->nullable()->index();
            $table->text('central_system_reference')->nullable();
            $table->text('cost_reference')->nullable();
            $table->text('payment_cash_reference')->nullable();
            $table->text('payment_noncash_reference')->nullable();
            $table->text('sales_cash_reference')->nullable();
            $table->text('sales_noncash_reference')->nullable();
            $table->text('dp_cash_order_reference')->nullable();
            $table->text('dp_noncash_order_reference')->nullable();
            $table->text('dp_cash_withdrawal_reference')->nullable();
            $table->text('dp_noncash_withdrawal_reference')->nullable();
            $table->text('credit_reference')->nullable();
            $table->text('refund_reference')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('closing_id')
                        ->references('id')->on('closings')
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
