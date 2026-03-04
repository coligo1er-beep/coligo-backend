<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateAllRoutesToAirplane extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // On s'assure d'abord que 'airplane' est bien dans l'ENUM (au cas où)
        DB::statement("ALTER TABLE routes MODIFY COLUMN vehicle_type ENUM('car', 'van', 'truck', 'motorcycle', 'airplane', 'boat', 'other') NOT NULL");

        // On met à jour tous les trajets existants vers 'airplane'
        DB::table('routes')->update(['vehicle_type' => 'airplane']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Pas de retour en arrière automatique possible vers les valeurs d'origine sans sauvegarde
    }
}
