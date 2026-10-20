<?php

namespace App\Http\Controllers;

use App\Models\SentenceSet;
use App\Services\ExampleSentences;
use App\Services\SentenceSetSidebar;
use Illuminate\Contracts\View\View;

class SentenceSetController extends Controller
{
    /**
     * Show a sentence set as the example sentences card of a lesson, with its sidebar (see SentenceSetSidebar).
     */
    public function __invoke(SentenceSet $sentenceSet, ExampleSentences $exampleSentences, SentenceSetSidebar $sidebar): View
    {
        $sentenceSet->load(['tags' => fn ($query) => $query->orderBy('name'), 'sentences']);

        abort_if($sentenceSet->sentences->isEmpty(), 404);

        return view('sentences.show', [
            'sentenceSet' => $sentenceSet,
            'exampleSentences' => $exampleSentences->for($sentenceSet->sentences),
            'sidebar' => $sidebar->for($sentenceSet),
        ]);
    }
}
