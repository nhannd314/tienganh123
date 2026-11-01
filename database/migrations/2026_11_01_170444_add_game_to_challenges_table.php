<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The brick-throwing game of an accepted challenge (see App\Services\ChallengeGame). Each player presses Start
     * (`*_ready_at`); once both have, `started_at` is set and `words` keeps the words of the lesson in the order they are
     * played. `word_index` is the word being typed, `*_progress` how many of its letters each player typed right, and
     * `*_health` what each has left out of `max_health`. When it ends, `finished_at` is set and `winner_id` is the winner
     * (null for a draw).
     */
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->timestamp('challenger_ready_at')->nullable()->after('responded_at');
            $table->timestamp('opponent_ready_at')->nullable()->after('challenger_ready_at');
            $table->timestamp('started_at')->nullable()->after('opponent_ready_at');
            $table->json('words')->nullable()->after('started_at');
            $table->unsignedSmallInteger('word_index')->default(0)->after('words');
            $table->unsignedSmallInteger('max_health')->nullable()->after('word_index');
            $table->unsignedSmallInteger('challenger_health')->nullable()->after('max_health');
            $table->unsignedSmallInteger('opponent_health')->nullable()->after('challenger_health');
            $table->unsignedSmallInteger('challenger_progress')->default(0)->after('opponent_health');
            $table->unsignedSmallInteger('opponent_progress')->default(0)->after('challenger_progress');
            $table->foreignId('winner_id')->nullable()->after('opponent_progress')->constrained('users')->nullOnDelete();
            $table->timestamp('finished_at')->nullable()->after('winner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('winner_id');
            $table->dropColumn([
                'challenger_ready_at',
                'opponent_ready_at',
                'started_at',
                'words',
                'word_index',
                'max_health',
                'challenger_health',
                'opponent_health',
                'challenger_progress',
                'opponent_progress',
                'finished_at',
            ]);
        });
    }
};
