<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeCoordinatesNullableInRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use raw SQL to bypass doctrine/dbal requirement
        DB::statement("ALTER TABLE routes MODIFY COLUMN departure_latitude DECIMAL(10, 8) NULL");
        DB::statement("ALTER TABLE routes MODIFY COLUMN departure_longitude DECIMAL(11, 8) NULL");
        DB::statement("ALTER TABLE routes MODIFY COLUMN arrival_latitude DECIMAL(10, 8) NULL");
        DB::statement("ALTER TABLE routes MODIFY COLUMN arrival_longitude DECIMAL(11, 8) NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE routes MODIFY COLUMN departure_latitude DECIMAL(10, 8) NOT NULL");
        DB::statement("ALTER TABLE routes MODIFY COLUMN departure_longitude DECIMAL(11, 8) NOT NULL");
        DB::statement("ALTER TABLE routes MODIFY COLUMN arrival_latitude DECIMAL(10, 8) NOT NULL");
        DB::statement("ALTER TABLE routes MODIFY COLUMN arrival_longitude DECIMAL(11, 8) NOT NULL");
    }
}
