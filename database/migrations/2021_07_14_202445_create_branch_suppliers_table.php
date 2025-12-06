<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchSuppliersTable extends Migration
{
    /**
     * DB Conenction
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'branch_suppliers';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('branch_id')->unsigned()->nullable()->index();
            $table->bigInteger('supplier_id')->unsigned()->nullable()->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('branch_id')
                        ->references('id')->on('branches')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
            $table->foreign('supplier_id')
                        ->references('id')->on('branches')
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
        Schema::connection($this->connection)->dropIfExists($this->table);
    }
}
