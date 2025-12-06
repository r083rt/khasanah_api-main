<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnMonitoringClosingCookiesTables extends Migration
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
    protected $table = 'monitoring_closing_cookies';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->integer('first_stock')->nullable()->after('adjustment');
            $table->integer('closing')->nullable()->after('first_stock');
            $table->integer('realization')->nullable()->after('closing');
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
            $table->dropColumn(['first_stock', 'closing', 'realization']);
        });
    }
}
