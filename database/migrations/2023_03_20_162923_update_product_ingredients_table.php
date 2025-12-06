<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProductIngredientsTable extends Migration
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
    protected $table2 = 'product_recipes';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn(['division_id']);
        });

        Schema::connection($this->connection)->table($this->table2, function (Blueprint $table) {
            $table->bigInteger('division_id')->unsigned()->nullable()->index()->after('id');
        });

        Schema::connection($this->connection)->table($this->table2, function (Blueprint $table) {
            $table->foreign('division_id')
                        ->references('id')->on('divisions')
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
        Schema::connection($this->connection)->table($this->table2, function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn(['division_id']);
        });
    }
}
