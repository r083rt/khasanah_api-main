<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyStatusInPoSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->enum('status', ['new', 'received', 'partial'])->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->enum('status', ['new', 'received'])->change();
        });
    }
}
