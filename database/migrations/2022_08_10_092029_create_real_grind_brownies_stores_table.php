<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRealGrindBrowniesStoresTable extends Migration
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'real_grind_brownies_stores';

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
            $table->bigInteger('master_packaging_id')->unsigned()->nullable()->index();
            $table->enum('type', ['brownies', 'sponge', 'cake'])->nullable();
            $table->date('date')->nullable();
            $table->integer('grind_to')->nullable();
            $table->double('grind_unit')->nullable();
            $table->integer('gramasi')->nullable();
            $table->double('qty_estimation')->nullable();
            $table->integer('qty_real')->nullable();
            $table->text('note')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('branch_id')
                        ->references('id')->on('branches')
                        ->onUpdate('cascade')
                        ->onDelete('cascade');
                $table->foreign('master_packaging_id')
                        ->references('id')->on('master_packagings')
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
