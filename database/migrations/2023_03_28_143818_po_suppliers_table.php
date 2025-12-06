<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PoSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('po_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->nullable();
            $table->tinyInteger('day')->nullable();
            $table->tinyInteger('month')->nullable();
            $table->year('year')->nullable();
            $table->date('date')->nullable();
            $table->bigInteger('branch_id')->unsigned()->nullable();
            $table->bigInteger('purchasing_supplier_id')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->foreign('branch_id')
                        ->references('id')->on('branches')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('purchasing_supplier_id')
                        ->references('id')->on('purchasing_suppliers')
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
        Schema::dropIfExists('po_suppliers');
    }
}
