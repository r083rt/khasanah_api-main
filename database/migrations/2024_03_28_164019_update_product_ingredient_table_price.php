<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProductIngredientTablePrice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_ingredients', function (Blueprint $table) {
            $table->integer('real_price')->nullable()->after('hpp');
            $table->integer('price')->nullable()->after('real_price');
            $table->integer('discount')->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_ingredients', function (Blueprint $table) {
            // $table->dropColumn('discount');
        });
    }
}
