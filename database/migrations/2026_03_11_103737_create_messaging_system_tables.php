<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagingSystemTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Conversations table - The container for messages
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['shipment', 'route', 'match'])->default('shipment');
            $table->unsignedBigInteger('source_id'); // ID of shipment, route or match
            $table->foreignId('participant_1_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('participant_2_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            // Prevent duplicate conversations for the same source between same participants
            $table->unique(['type', 'source_id', 'participant_1_id', 'participant_2_id'], 'unique_conversation');
        });

        // 2. Messages table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->enum('message_type', ['text', 'image', 'audio', 'location', 'system'])->default('text');
            $table->string('attachment_path')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('sender_id');
        });

        // 3. User Blocks table
        Schema::create('user_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('blocked_id')->constrained('users')->onDelete('cascade');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
}
