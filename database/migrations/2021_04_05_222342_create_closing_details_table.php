<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClosingDetailsTable extends Migration
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
    protected $table = 'closing_details';

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
            $table->integer('local_system')->nullable();
            $table->integer('central_system')->nullable();
            $table->integer('deposit_difference')->nullable();
            $table->integer('cost')->nullable();
            $table->integer('payment_cash')->nullable();
            $table->integer('payment_noncash')->nullable();
            $table->integer('sales_cash')->nullable();
            $table->integer('sales_noncash')->nullable();
            $table->integer('local_central_difference')->nullable();
            $table->integer('dp_cash_order')->nullable();
            $table->integer('dp_noncash_order')->nullable();
            $table->integer('dp_cash_withdrawal')->nullable();
            $table->integer('dp_noncash_withdrawal')->nullable();
            $table->integer('credit')->nullable();
            $table->integer('refund')->nullable();
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
