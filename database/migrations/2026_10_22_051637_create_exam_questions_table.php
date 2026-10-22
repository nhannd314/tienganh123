<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A question written by hand in an exam group: a choice among `options` (`answer` is the number of the right option,
     * from 1), or a word to fill in (`answer` lists the accepted answers, separated by "|").
     */
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_group_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->text('prompt');
            $table->json('options')->nullable();
            $table->string('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['exam_group_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
