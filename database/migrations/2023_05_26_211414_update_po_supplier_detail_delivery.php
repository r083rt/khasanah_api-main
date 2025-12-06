<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePoSupplierDetailDelivery extends Migration
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
    protected $table = 'po_supplier_details';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->integer('qty_received')->nullable()->after('qty');
            $table->text('note')->nullable()->after('barcode');
            $table->bigInteger('received_by')->unsigned()->nullable()->after('note');
            $table->index('barcode');
        });

        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->foreign('received_by')
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
        Schema::connection($this->connection)->table($this->table, function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropForeign(['received_by']);
            $table->dropColumn(['qty_received', 'note', 'received_by']);
        });
    }
}
