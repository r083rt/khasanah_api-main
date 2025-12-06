<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSoYearTable extends Migration
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
    protected $table = 'stock_opnames';
    protected $table2 = 'stock_opname_imports';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->year('year', 4)->nullable()->after('month');
        });

        Schema::connection($this->connection)->table($this->table2, function (Blueprint $table) {
            $table->year('year', 4)->nullable()->after('month');
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
            $table->dropColumn(['year']);
        });

        Schema::connection($this->connection)->table($this->table2, function (Blueprint $table) {
            $table->dropColumn(['year']);
        });
    }
}
