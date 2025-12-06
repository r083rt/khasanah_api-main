<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoOrderProductStatusLogs extends Migration
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
    protected $table = 'po_order_product_status_logs';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('po_order_product_id')->unsigned()->nullable()->index();
            $table->enum('status', ['new', 'processed', 'product_accepted', 'product_rejected', 'print', 'product_incomplete', 'done', 'rejected', 'print-po'])->nullable()->default('new');
            $table->text('note')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('po_order_product_id')
                        ->references('id')->on('po_order_products')
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
