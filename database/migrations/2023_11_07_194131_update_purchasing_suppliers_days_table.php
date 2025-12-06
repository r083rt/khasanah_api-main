<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePurchasingSuppliersDaysTable extends Migration
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
    protected $table = 'purchasing_suppliers';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->dropColumn(['day']);
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->tinyInteger('day')->nullable()->after('payment');
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
            $table->dropColumn(['day']);
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->enum('day', ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'])->nullable()->after('payment');
        });
    }
}
