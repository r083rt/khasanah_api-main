<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_transactions', function (Blueprint $table) {
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_transactions');
    }
}
