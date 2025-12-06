<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIngredientUsagesTable extends Migration
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
    protected $table = 'ingredient_usages';

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
            $table->bigInteger('ingredient_id')->unsigned()->nullable()->index();
            $table->string('branch_name')->nullable();
            $table->date('date')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->double('qty')->nullable();
            $table->string('unit_name')->nullable();
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
