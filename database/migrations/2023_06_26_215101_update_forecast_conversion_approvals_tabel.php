<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForecastConversionApprovalsTabel extends Migration
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
    protected $table = 'forecast_conversion_approvals';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->bigInteger('parent_id')->unsigned()->nullable()->after('id');
            $table->bigInteger('stock_opname_id')->unsigned()->nullable()->after('parent_id');
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('parent_id')
                        ->references('id')->on('forecast_conversion_approvals')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('stock_opname_id')
                        ->references('id')->on('stock_opnames')
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
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['stock_opname_id']);
            $table->dropColumn(['parent_id', 'stock_opname_id']);
        });
    }
}
