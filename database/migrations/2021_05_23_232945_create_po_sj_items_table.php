<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoSjItemsTable extends Migration
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'po_sj_items';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('po_sj_id')->unsigned()->nullable()->index();
            $table->bigInteger('branch_id')->unsigned()->nullable()->index();
            $table->bigInteger('po_id')->unsigned()->nullable()->index();
            $table->enum('type', ['po_order_product', 'po_order_ingredient', 'po_request_product', 'po_request_ingredient', 'po_central_product', 'po_central_ingredient'])->nullable();
            $table->string('box_name')->nullable();
            $table->string('code_item')->nullable();
            $table->string('name_item')->nullable();
            $table->integer('qty')->nullable();
            $table->string('unit_name')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('po_sj_id')
                        ->references('id')->on('po_sj')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('branch_id')
                        ->references('id')->on('branches')
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
