<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBrandIdColumnProductIngredientsTable extends Migration
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
    protected $table = 'product_ingredients';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->bigInteger('brand_id')->unsigned()->nullable()->index()->after('unit_value');
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('brand_id')
                        ->references('id')->on('brands')
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
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['brand_id']);
        });
    }
}
