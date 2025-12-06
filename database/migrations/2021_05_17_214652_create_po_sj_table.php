<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoSjTable extends Migration
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'po_sj';

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
            $table->string('sj_number')->nullable()->unique();
            $table->string('vehicle_number')->nullable();
            $table->date('delivery_date')->nullable();
            $table->enum('status', ['product_accepted', 'print'])->nullable()->default('product_accepted');
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('shipping_id')
                        ->references('id')->on('shippings')
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
