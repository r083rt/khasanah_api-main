<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoManualsTable extends Migration
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
    protected $table = 'po_manuals';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shipping_id')->unsigned()->nullable()->index();
            $table->bigInteger('branch_id')->unsigned()->nullable()->index();
            $table->boolean('is_urgent')->nullable();
            $table->string('nomor_po')->nullable();
            $table->enum('type', ['product', 'ingredient'])->nullable();
            $table->enum('status_shipping', ['today', 'tomorrow'])->nullable();
            $table->enum('status', ['new', 'pending', 'po-accepted', 'po-rejected', 'processed', 'product_accepted', 'product_rejected', 'print', 'product_incomplete', 'done', 'rejected', 'print-po'])->nullable()->default('new');
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('branch_id')
                        ->references('id')->on('branches')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('shipping_id')
                        ->references('id')->on('shippings')
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
