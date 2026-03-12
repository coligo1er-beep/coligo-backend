<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationSystemTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Badges Reference table
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // identity_verified, expert, super_transporter
            $table->string('icon_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Reviews table
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewed_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('rating'); // 1 to 5
            $table->text('comment')->nullable();
            $table->json('criteria')->nullable(); // detailed notes
            $table->text('response')->nullable(); // response from the reviewed person
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            // One review per match per reviewer
            $table->unique(['match_id', 'reviewer_id']);
        });

        // 3. User Badges pivot
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('badge_id')->constrained('badges')->onDelete('cascade');
            $table->timestamp('achieved_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'badge_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('badges');
    }
}
