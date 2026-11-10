<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('word_scene_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('word_scene_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            // The rectangle around the object, as fractions (0 to 1) of the picture width and height; null until it is drawn.
            $table->decimal('x', 7, 6)->nullable();
            $table->decimal('y', 7, 6)->nullable();
            $table->decimal('width', 7, 6)->nullable();
            $table->decimal('height', 7, 6)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['word_scene_id', 'word_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_scene_items');
    }
};
