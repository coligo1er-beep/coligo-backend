<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentTrackingAndProofsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Shipment Trackings - GPS History
        Schema::create('shipment_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('speed')->nullable();
            $table->timestamps();

            $table->index('shipment_id');
        });

        // 2. Delivery Proofs - Photos and Signatures
        Schema::create('delivery_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['pickup_photo', 'delivery_photo', 'sender_signature']);
            $table->string('file_path');
            $table->json('metadata')->nullable(); // GPS, device info, etc.
            $table->timestamps();

            $table->index('shipment_id');
        });

        // 3. Shipment Status History - For audit
        Schema::create('shipment_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('shipment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipment_status_histories');
        Schema::dropIfExists('delivery_proofs');
        Schema::dropIfExists('shipment_trackings');
    }
}
