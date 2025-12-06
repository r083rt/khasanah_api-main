<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchesTable extends Migration
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
    protected $table = 'branches';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->id();
            $table->bigInteger('territory_id')->unsigned()->nullable()->index();
            $table->bigInteger('area_id')->unsigned()->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->integer('zip_code')->nullable();
            $table->enum('material_delivery_type', ['daily', 'three_days', 'weekly', 'monthly'])->nullable();
            $table->enum('schedule', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable();
            $table->enum('discount_active', ['store', 'promo'])->nullable();
            $table->string('address')->nullable();
            $table->integer('initial_capital')->nullable();
            $table->string('note')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('territory_id')
                        ->references('id')->on('territories')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('area_id')
                        ->references('id')->on('areas')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
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
