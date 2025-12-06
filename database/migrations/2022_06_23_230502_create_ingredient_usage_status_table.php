<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIngredientUsageStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ingredient_usage_status', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('branch_id')->unsigned()->nullable()->index();
            $table->date('date')->nullable();
            $table->enum('status_po_production_cookie', ['new', 'done'])->nullable();
            $table->enum('status_po_production_brownies', ['new', 'done'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ingredient_usage_status');
    }
}
