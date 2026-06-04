<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeVendorIdForeignOnOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {

            // Drop old foreign key
            $table->dropForeign(['vendor_id']);

            // Make column nullable
            $table->unsignedBigInteger('vendor_id')
                  ->nullable()
                  ->change();

            // Add new foreign key
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
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
        Schema::table('order_items', function (Blueprint $table) {

            $table->dropForeign(['vendor_id']);

            $table->unsignedBigInteger('vendor_id')
                  ->nullable(false)
                  ->change();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->onDelete('cascade');
        });
    }
}
