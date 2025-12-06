<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportTransactionCurrentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('report')->create('report_transaction_currents', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->bigInteger('product_category_id')->unsigned()->nullable();
            $table->string('product_category_name')->nullable();
            $table->bigInteger('branch_id')->unsigned()->nullable();
            $table->string('branch_name')->nullable();
            $table->integer('qty')->nullable();
            $table->integer('total_price')->nullable();
            $table->timestamps();
        });

        Schema::connection('report')->table('report_transaction_currents', function (Blueprint $table) {
            $table->index(['date', 'start_time', 'end_time', 'product_category_id', 'branch_id'], 'report');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('report')->dropIfExists('report_transaction_currents');
    }
}
