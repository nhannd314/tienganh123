<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The fastest time in milliseconds a user won a game of a lesson in (see App\Models\GameScore), kept to rank the players.
     */
    public function up(): void
    {
        Schema::create('game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game', 50);
            $table->unsignedInteger('milliseconds');
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id', 'game']);
            $table->index(['lesson_id', 'game', 'milliseconds']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_scores');
    }
};
