<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceivePoSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receive_po_suppliers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('po_supplier_id')->unsigned()->nullable();
            $table->string('rg_number')->nullable();
            $table->date('received_at')->nullable();
            $table->bigInteger('received_by')->unsigned()->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::table('receive_po_suppliers', function (Blueprint $table) {
            $table->foreign('po_supplier_id')
                        ->references('id')->on('po_suppliers')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
        });

        Schema::table('receive_po_suppliers', function (Blueprint $table) {
            $table->foreign('received_by')
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
        Schema::dropIfExists('receive_po_suppliers');
    }
}
