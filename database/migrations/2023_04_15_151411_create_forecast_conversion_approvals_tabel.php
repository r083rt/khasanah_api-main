<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForecastConversionApprovalsTabel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forecast_conversion_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('pr_id');
            $table->tinyInteger('month')->nullable();
            $table->year('year')->nullable();
            $table->enum('type', ['default', 'so'])->default('default')->nullable();
            $table->enum('status', ['submitted', 'approved', 'setting-po'])->default('submitted')->nullable();
            $table->bigInteger('submitted_by')->unsigned()->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->bigInteger('approved_by')->unsigned()->nullable();
            $table->dateTime('approved_at')->nullable()->nullable();
            $table->timestamps();
        });

        Schema::table('forecast_conversion_approvals', function (Blueprint $table) {
            $table->foreign('submitted_by')
                        ->references('id')->on('users')
                        ->onUpdate('cascade')
                        ->onDelete('set null');
            $table->foreign('approved_by')
                        ->references('id')->on('users')
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
        Schema::dropIfExists('forecast_conversion_approvals');
    }
}
