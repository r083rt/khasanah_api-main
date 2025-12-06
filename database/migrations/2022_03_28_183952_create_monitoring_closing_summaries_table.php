<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitoringClosingSummariesTable extends Migration
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'monitoring_closing_summaries';

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
            $table->string('type')->nullable();
            $table->integer('first_stock')->nullable();
            $table->integer('in')->nullable();
            $table->integer('sale')->nullable();
            $table->integer('order')->nullable();
            $table->integer('return')->nullable();
            $table->integer('transfer_stock')->nullable();
            $table->integer('remains_closing')->nullable();
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
