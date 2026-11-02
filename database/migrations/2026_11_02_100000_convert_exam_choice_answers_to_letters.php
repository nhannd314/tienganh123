<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The right answer of a choice question was the number of its option (1, 2, 3...); it is now the letter (a, b, c...).
     */
    public function up(): void
    {
        foreach (range(1, 26) as $number) {
            DB::table('exam_questions')
                ->where('type', 'choice')
                ->where('answer', (string) $number)
                ->update(['answer' => chr(96 + $number)]);
        }
    }

    public function down(): void
    {
        foreach (range(1, 26) as $number) {
            DB::table('exam_questions')
                ->where('type', 'choice')
                ->where('answer', chr(96 + $number))
                ->update(['answer' => (string) $number]);
        }
    }
};
