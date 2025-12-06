<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForecastConversionApprovalDetailFcsTable extends Migration
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
    protected $table = 'forecast_conversion_details';
    protected $table2 = 'forecast_conversion_approval_details';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->double('conversion_latest_rounding_total')->nullable()->after('conversion_latest_rounding');
        });

        Schema::connection($this->connection)->table($this->table2, function (Blueprint $table) {
            $table->double('conversion_latest_rounding_total')->nullable()->after('conversion_latest_rounding');
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
            $table->dropColumn(['conversion_latest_rounding_total']);
        });

        Schema::connection($this->connection)->table($this->table2, function (Blueprint $table) {
            $table->dropColumn(['conversion_latest_rounding_total']);
        });
    }
}
