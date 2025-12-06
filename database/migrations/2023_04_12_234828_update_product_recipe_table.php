<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProductRecipeTable extends Migration
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
    protected $table = 'product_recipes';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->bigInteger('master_packaging_id')->unsigned()->nullable()->index()->after('id');
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('master_packaging_id')
                        ->references('id')->on('master_packagings')
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
            $table->dropForeign(['master_packaging_id']);
            $table->dropColumn(['master_packaging_id']);
        });
    }
}
