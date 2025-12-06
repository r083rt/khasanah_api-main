<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTableProductRecipeUnits extends Migration
{
    /**
     * DB Connection
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'product_recipe_units';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->bigInteger('parent_id_2')->unsigned()->nullable()->after('name')->index();
            $table->double('parent_id_2_conversion')->nullable()->after('parent_id_2');
            $table->bigInteger('parent_id_3')->unsigned()->nullable()->after('parent_id_2_conversion')->index();
            $table->double('parent_id_3_conversion')->nullable()->after('parent_id_3');
            $table->bigInteger('parent_id_4')->unsigned()->nullable()->after('parent_id_3_conversion')->index();
            $table->double('parent_id_4_conversion')->nullable()->after('parent_id_4');
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('parent_id_2')
                        ->references('id')->on('product_recipe_units')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('parent_id_3')
                        ->references('id')->on('product_recipe_units')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('parent_id_4')
                        ->references('id')->on('product_recipe_units')
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
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->dropForeign(['parent_id_2']);
            $table->dropForeign(['parent_id_3']);
            $table->dropForeign(['parent_id_4']);
            $table->dropColumn(['parent_id_2', 'parent_id_2_conversion', 'parent_id_3', 'parent_id_3_conversion', 'parent_id_4', 'parent_id_4_conversion']);
        });
    }
}
