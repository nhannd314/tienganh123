<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The best score a user reached in a lesson (see App\Services\LessonScoreCalculator), kept to rank the learners.
     */
    public function up(): void
    {
        Schema::create('lesson_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score');
            $table->unsignedTinyInteger('percent')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
            $table->index(['lesson_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_scores');
    }
};
