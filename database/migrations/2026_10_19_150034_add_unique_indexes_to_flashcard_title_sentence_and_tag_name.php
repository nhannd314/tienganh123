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
        Schema::table('flashcards', function (Blueprint $table) {
            $table->unique('title');
        });

        Schema::table('sentences', function (Blueprint $table) {
            $table->unique('sentence');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flashcards', function (Blueprint $table) {
            $table->dropUnique(['title']);
        });

        Schema::table('sentences', function (Blueprint $table) {
            $table->dropUnique(['sentence']);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
