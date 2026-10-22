<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A group of questions of an exam (see App\Enums\ExamGroupType). Each question of the group is worth `weight` times a
     * question of weight 1. The word groups list their words in `word_ids`; the "listen and fill" group has a `passage`
     * with the words to fill in wrapped in backticks, and its `audio`.
     */
    public function up(): void
    {
        Schema::create('exam_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->decimal('weight', 5, 2)->default(1);
            $table->json('word_ids')->nullable();
            $table->text('passage')->nullable();
            $table->string('audio')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['exam_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_groups');
    }
};
