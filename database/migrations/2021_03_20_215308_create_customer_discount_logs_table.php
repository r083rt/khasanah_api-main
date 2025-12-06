<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDiscountLogsTable extends Migration
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
    protected $table = 'customer_discount_logs';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_discount_id')->unsigned()->nullable()->index();
            $table->integer('discount_old')->nullable();
            $table->integer('discount_new')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('customer_discount_id')
                        ->references('id')->on('customer_discounts')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('created_by')
                        ->references('id')->on('users')
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
        Schema::connection($this->connection)->dropIfExists($this->table);
    }
}
