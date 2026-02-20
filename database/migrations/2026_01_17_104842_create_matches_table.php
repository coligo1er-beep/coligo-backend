<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->foreignId('transporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->decimal('proposed_price', 10, 2);
            $table->decimal('final_price', 10, 2)->nullable();
            $table->datetime('pickup_datetime')->nullable();
            $table->datetime('delivery_datetime')->nullable();
            $table->decimal('matching_score', 5, 2)->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('estimated_duration_hours', 5, 2)->nullable();
            $table->text('transporter_message')->nullable();
            $table->text('sender_response')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['shipment_id']);
            $table->index(['route_id']);
            $table->index(['transporter_id']);
            $table->index(['sender_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('matches');
    }
}
