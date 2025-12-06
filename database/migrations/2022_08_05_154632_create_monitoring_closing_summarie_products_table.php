<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitoringClosingSummarieProductsTable extends Migration
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'monitoring_closing_summary_products';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table('monitoring_closing_summaries', function (Blueprint $table) {
            $table->bigInteger('product_category_id')->unsigned()->nullable()->index()->after('id');
        });

        Schema::connection($this->connection)->table('monitoring_closing_summaries', function (Blueprint $table) {
            $table->foreign('product_category_id')
                        ->references('id')->on('product_categories')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
        });

        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('monitoring_closing_summary_id')->unsigned()->nullable()->index('monitoring_closing_summary_id_products_index');
            $table->bigInteger('branch_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_category_id')->unsigned()->nullable()->index();
            $table->date('date')->nullable();
            $table->integer('first_stock')->nullable();
            $table->integer('in')->nullable();
            $table->integer('sale')->nullable();
            $table->integer('order')->nullable();
            $table->integer('return')->nullable();
            $table->integer('transfer_stock')->nullable();
            $table->integer('remains_closing')->nullable();
            $table->integer('difference')->nullable();
            $table->integer('hpp_total')->nullable();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('monitoring_closing_summary_id', 'monitoring_closing_summary_id_product_foerign')
                        ->references('id')->on('monitoring_closing_summaries')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_id')
                        ->references('id')->on('products')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('branch_id')
                        ->references('id')->on('branches')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('product_category_id')
                        ->references('id')->on('product_categories')
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
        Schema::connection($this->connection)->table('monitoring_closing_summaries', function (Blueprint $table) {
            $table->dropForeign(['product_category_id']);
            $table->dropColumn(['product_category_id']);
        });
    }
}
