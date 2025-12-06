<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnExpenseCostTable extends Migration
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
    protected $table = 'expenses';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection($this->connection)->statement("ALTER TABLE $this->table CHANGE `cost` `cost` double;");
        DB::connection($this->connection)->statement("ALTER TABLE $this->table CHANGE `total_cost` `total_cost` double;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection($this->connection)->statement("ALTER TABLE $this->table CHANGE `cost` `cost` integer;");
        DB::connection($this->connection)->statement("ALTER TABLE $this->table CHANGE `total_cost` `total_cost` integer;");
    }
}
