<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateVehicleTypeInRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // For MySQL, we need to use a raw query to update the enum
        DB::statement("ALTER TABLE routes MODIFY COLUMN vehicle_type ENUM('car', 'van', 'truck', 'motorcycle', 'airplane', 'boat', 'other') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE routes MODIFY COLUMN vehicle_type ENUM('car', 'van', 'truck', 'motorcycle', 'other') NOT NULL");
    }
}
