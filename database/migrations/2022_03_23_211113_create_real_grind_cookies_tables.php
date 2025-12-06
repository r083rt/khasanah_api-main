<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRealGrindCookiesTables extends Migration
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'real_grind_cookies';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->integer('grind_to')->nullable();
            $table->double('grind_unit')->nullable();
            $table->double('total_press')->nullable();
            $table->integer('gram_unit')->nullable();
            $table->integer('total_product')->nullable();
            $table->text('note')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
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
