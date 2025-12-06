<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdatePackagingType extends Migration
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
    protected $table = 'master_packagings';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection($this->connection)->statement("ALTER TABLE $this->table CHANGE `type` `type` ENUM('brownies','sponge','cake', 'cookie', 'bread', 'cream');");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection($this->connection)->statement("ALTER TABLE $this->table CHANGE `type` `type` ENUM('brownies','sponge','cake', 'cookie');");
    }
}
