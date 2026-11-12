<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One slide of a slideshow: an image (a path on the public disk) and the text shown with it, in `position` order.
     */
    public function up(): void
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slide_deck_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('image');
            $table->text('text')->nullable();
            $table->timestamps();

            $table->index(['slide_deck_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
