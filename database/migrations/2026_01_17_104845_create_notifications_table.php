<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['new_match', 'status_update', 'message', 'reminder', 'system']);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->json('channels'); // ['push', 'sms', 'email']
            $table->enum('status', ['pending', 'sent', 'failed', 'read'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('related_type')->nullable(); // 'shipment', 'route', 'match'
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            $table->index(['type']);
            $table->index(['status']);
            $table->index(['priority']);
            $table->index(['related_type', 'related_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
