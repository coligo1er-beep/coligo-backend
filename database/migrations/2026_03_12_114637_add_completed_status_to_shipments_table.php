<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddCompletedStatusToShipmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE shipments MODIFY COLUMN status ENUM('draft', 'published', 'matched', 'in_transit', 'delivered', 'cancelled', 'completed') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE shipments MODIFY COLUMN status ENUM('draft', 'published', 'matched', 'in_transit', 'delivered', 'cancelled') DEFAULT 'draft'");
    }
}
