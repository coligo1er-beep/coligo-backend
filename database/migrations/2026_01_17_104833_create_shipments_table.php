<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->decimal('weight', 8, 2);
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->boolean('fragile')->default(false);
            $table->boolean('dangerous_goods')->default(false);
            $table->string('pickup_address');
            $table->string('pickup_city');
            $table->string('pickup_postal_code')->nullable();
            $table->string('pickup_country');
            $table->decimal('pickup_latitude', 10, 8);
            $table->decimal('pickup_longitude', 11, 8);
            $table->datetime('pickup_date_from');
            $table->datetime('pickup_date_to');
            $table->string('delivery_address');
            $table->string('delivery_city');
            $table->string('delivery_postal_code')->nullable();
            $table->string('delivery_country');
            $table->decimal('delivery_latitude', 10, 8);
            $table->decimal('delivery_longitude', 11, 8);
            $table->datetime('delivery_date_limit');
            $table->decimal('budget_min', 10, 2);
            $table->decimal('budget_max', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['draft', 'published', 'matched', 'in_transit', 'delivered', 'cancelled'])->default('draft');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->text('special_instructions')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['pickup_latitude', 'pickup_longitude']);
            $table->index(['delivery_latitude', 'delivery_longitude']);
            $table->index(['pickup_date_from', 'delivery_date_limit']);
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
        Schema::dropIfExists('shipments');
    }
}
