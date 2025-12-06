<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateApprovalBranchDetail extends Migration
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
    protected $table = 'forecast_conversion_approval_detail_branches';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->dropColumn(['qty_real', 'buffer']);

            $table->double('qty_forecast')->nullable()->after('product_ingredient_id');
            $table->double('qty_so')->nullable()->after('qty_forecast');

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
            $table->dropColumn(['qty_forecast', 'qty_so']);

            $table->double('qty_real')->nullable()->after('product_ingredient_id');
            $table->double('buffer')->nullable()->after('qty_real');
        });
    }
}
