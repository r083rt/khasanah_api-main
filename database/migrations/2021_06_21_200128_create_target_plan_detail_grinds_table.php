<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTargetPlanDetailGrindsTable extends Migration
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'target_plan_detail_grinds';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('target_plan_detail_id')->unsigned()->nullable()->index();
            $table->integer('grind')->nullable()->index();
            $table->integer('total')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('target_plan_detail_id')
                        ->references('id')->on('target_plan_details')
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
