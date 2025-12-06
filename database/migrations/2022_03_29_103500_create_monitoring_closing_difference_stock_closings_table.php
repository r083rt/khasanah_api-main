<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitoringClosingDifferenceStockClosingsTable extends Migration
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'monitoring_closing_difference_stocks';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('branch_id')->unsigned()->nullable()->index();
            $table->date('date')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_category_name')->nullable();
            $table->integer('difference')->nullable();
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
