<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCustomerIdForeignOnOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
           Schema::table('orders', function (Blueprint $table) {

            // Drop old foreign key
            $table->dropForeign(['customer_id']);

            // Make column nullable
            $table->unsignedBigInteger('customer_id')
                  ->nullable()
                  ->change();

            // Add new foreign key
            $table->foreign('customer_id')
                ->references('id')
                ->on('users')
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
         Schema::table('orders', function (Blueprint $table) {

            $table->dropForeign(['customer_id']);

            $table->unsignedBigInteger('customer_id')
                  ->nullable(false)
                  ->change();

            $table->foreign('customer_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
}
