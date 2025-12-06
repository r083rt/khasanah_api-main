<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportingRecipesTable extends Migration
{
    /**
     * DB Connection
     *
     * @var string
     */
    protected $connection = 'report';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('report_recipes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('master_packaging_id')->unsigned()->nullable()->index();
            $table->bigInteger('product_id')->unsigned()->nullable()->index();
            $table->string('product_name')->nullable();
            $table->string('product_code')->nullable();
            $table->bigInteger('product_ingredient_id')->unsigned()->nullable()->index();
            $table->string('ingredient_name')->nullable();
            $table->string('ingredient_code')->nullable();
            $table->string('qty')->nullable();
            $table->bigInteger('product_recipe_unit_id')->unsigned()->nullable()->index();
            $table->string('unit_name')->nullable();
            $table->text('logs')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table('report_recipes', function (Blueprint $table) {
            $table->index(['product_id', 'product_ingredient_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('report_recipes');
    }
}
