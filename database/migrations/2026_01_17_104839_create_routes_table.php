<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('departure_address');
            $table->string('departure_city');
            $table->string('departure_country');
            $table->decimal('departure_latitude', 10, 8);
            $table->decimal('departure_longitude', 11, 8);
            $table->datetime('departure_date_from');
            $table->datetime('departure_date_to');
            $table->string('arrival_address');
            $table->string('arrival_city');
            $table->string('arrival_country');
            $table->decimal('arrival_latitude', 10, 8);
            $table->decimal('arrival_longitude', 11, 8);
            $table->datetime('arrival_date_from');
            $table->datetime('arrival_date_to');
            $table->decimal('total_capacity_kg', 8, 2);
            $table->decimal('available_capacity_kg', 8, 2);
            $table->enum('vehicle_type', ['car', 'van', 'truck', 'motorcycle', 'other']);
            $table->string('vehicle_description')->nullable();
            $table->decimal('price_per_kg', 10, 2)->nullable();
            $table->decimal('min_shipment_price', 10, 2)->nullable();
            $table->enum('status', ['draft', 'published', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->boolean('recurring')->default(false);
            $table->json('recurring_pattern')->nullable();
            $table->text('special_conditions')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['departure_latitude', 'departure_longitude']);
            $table->index(['arrival_latitude', 'arrival_longitude']);
            $table->index(['departure_date_from', 'arrival_date_to']);
            $table->index(['available_capacity_kg']);
            $table->index(['status']);
            $table->index(['user_id']);
            $table->index(['published_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routes');
    }
}
