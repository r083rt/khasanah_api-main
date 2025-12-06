<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnPoSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('return_po_suppliers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('po_supplier_id')->unsigned()->nullable();
            $table->string('rt_number')->nullable();
            $table->date('returned_at')->nullable();
            $table->bigInteger('returned_by')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::table('return_po_suppliers', function (Blueprint $table) {
            $table->foreign('po_supplier_id')
                        ->references('id')->on('po_suppliers')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
        });

        Schema::table('return_po_suppliers', function (Blueprint $table) {
            $table->foreign('returned_by')
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
        Schema::dropIfExists('return_po_suppliers');
    }
}
