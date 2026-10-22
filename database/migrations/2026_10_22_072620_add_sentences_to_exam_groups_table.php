<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The English sentences read aloud in a sentence pronunciation group (see App\Enums\ExamGroupType::SentencePronunciation).
     */
    public function up(): void
    {
        Schema::table('exam_groups', function (Blueprint $table) {
            $table->json('sentences')->nullable()->after('word_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_groups', function (Blueprint $table) {
            $table->dropColumn('sentences');
        });
    }
};
