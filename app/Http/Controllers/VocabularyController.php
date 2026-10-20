<?php

namespace App\Http\Controllers;

use App\Models\Vocabulary;
use App\Services\ExampleSentences;
use App\Services\VocabularySidebar;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

class VocabularyController extends Controller
{
    /**
     * Show a vocabulary with its word table and example sentences, and its sidebar (see VocabularySidebar).
     */
    public function __invoke(Vocabulary $vocabulary, ExampleSentences $exampleSentences, VocabularySidebar $sidebar): View
    {
        $vocabulary->load(['words', 'sentenceSet.sentences', 'tags' => fn ($query) => $query->orderBy('name')]);

        return view('vocabularies.show', [
            'vocabulary' => $vocabulary,
            'words' => $vocabulary->words,
            'exampleSentences' => $exampleSentences->for($vocabulary->sentenceSet?->sentences ?? new Collection),
            'sidebar' => $sidebar->for($vocabulary),
        ]);
    }
}
