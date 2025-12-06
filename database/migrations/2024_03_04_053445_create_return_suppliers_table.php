<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('return_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->nullable();
            $table->bigInteger('supplier_id')->unsigned()->nullable();
            $table->bigInteger('po_supplier_id')->unsigned()->nullable();
            $table->date('return_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::table('return_suppliers', function (Blueprint $table) {
            $table->foreign('po_supplier_id')
                        ->references('id')->on('po_suppliers')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
        });

        Schema::table('return_suppliers', function (Blueprint $table) {
            $table->foreign('supplier_id')
                        ->references('id')->on('purchasing_suppliers')
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
        Schema::dropIfExists('return_suppliers');
    }
}
