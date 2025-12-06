<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForecastConversionSettingPosTabel extends Migration
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
    protected $table = 'forecast_conversion_setting_po';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->bigInteger('forecast_conversion_approval_id')->unsigned()->nullable()->after('id');
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('forecast_conversion_approval_id', 'fcsp_fca_id_foreign')
                        ->references('id')->on('forecast_conversion_approvals')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->dropForeign(['forecast_conversion_id']);
            $table->dropColumn(['forecast_conversion_id']);
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
            $table->dropForeign('fcsp_fca_id_foreign');
            $table->dropColumn(['forecast_conversion_approval_id']);
        });
    }
}
