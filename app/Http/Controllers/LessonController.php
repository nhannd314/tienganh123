<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Unit;
use App\Models\Word;
use DOMDocument;
use DOMElement;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    /**
     * Show a published lesson with its vocabulary table, flashcards and content.
     */
    public function __invoke(Request $request, Course $course, Unit $unit, Lesson $lesson): View
    {
        abort_unless($course->is_published && $unit->is_published && $lesson->is_published, 404);

        $lesson->load(['flashcardSets.flashcards.word']);

        $words = $lesson->words;
        $exampleSentences = $words
            ->map(function (Word $word): ?array {
                $example = collect($word->examples ?? [])->first(fn ($example): bool => is_array($example) && filled($example['sentence'] ?? null));

                return $example ? [
                    'word' => $word,
                    'sentence' => $example['sentence'],
                    'translation' => $example['translation'] ?? null,
                    'audioUrl' => filled($example['audio'] ?? null) ? Storage::disk('public')->url($example['audio']) : null,
                    'parts' => $word->exampleSentenceParts($example['sentence']),
                ] : null;
            })
            ->filter()
            ->values();

        return view('lessons.show', [
            'course' => $course,
            'unit' => $unit,
            'lesson' => $lesson,
            'words' => $words,
            'exampleSentences' => $exampleSentences,
            'flashcardSets' => $lesson->flashcardSets,
            'passageHtml' => $this->richContentHtml($lesson->passage, 'passage-word-'),
            'contentHtml' => $this->richContentHtml($lesson->content, 'word-'),
            'attachments' => $this->attachments($lesson),
            'progress' => $request->user() ? $lesson->reviewProgress($request->user()) : null,
            'enrollCourse' => $course->needsEnrollmentBy($request->user()) ? $course : null,
            'topLearners' => $this->lessonTopLearners($lesson),
            'standing' => $request->user() ? $this->lessonStanding($lesson, $request->user()) : null,
        ]);
    }

    /**
     * Render the rich content, turning inline code that matches a stored word into a popover label.
     *
     * Inline code that matches no word is shown as plain text. Each popover id is `$popoverIdPrefix` plus its position,
     * so two rendered fields on one page never share an id.
     */
    private function richContentHtml(?string $content, string $popoverIdPrefix): string
    {
        if (blank($content)) {
            return '';
        }

        $html = RichContentRenderer::make($content)
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->toHtml();

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8"?><div id="rich-content">'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);

        $codes = array_values(array_filter(
            iterator_to_array($document->getElementsByTagName('code')),
            fn (DOMElement $code): bool => $code->parentNode?->nodeName !== 'pre',
        ));

        if ($codes === []) {
            return $html;
        }

        $words = Word::query()
            ->whereIn(DB::raw('lower(word)'), collect($codes)->map(fn (DOMElement $code): string => mb_strtolower(trim($code->textContent)))->unique()->all())
            ->get()
            ->keyBy(fn (Word $word): string => mb_strtolower($word->word));

        $popovers = [];

        foreach ($codes as $index => $code) {
            $word = $words->get(mb_strtolower(trim($code->textContent)));
            $token = '@@'.$popoverIdPrefix.$index.'@@';

            $popovers[$token] = $word
                ? Blade::render('<x-word-popover :word="$word" :popover-id="$popoverId" />', ['word' => $word, 'popoverId' => $popoverIdPrefix.$index])
                : e($code->textContent);

            $code->parentNode->replaceChild($document->createTextNode($token), $code);
        }

        $output = '';

        foreach ($document->getElementById('rich-content')->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return strtr($output, $popovers);
    }

    /**
     * Files attached to the lesson, flagged when they can be played in the browser.
     *
     * @return list<array{name: string, url: string, isAudio: bool}>
     */
    private function attachments(Lesson $lesson): array
    {
        $disk = Storage::disk('public');

        return collect($lesson->attachments ?? [])
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'url' => $disk->url($path),
                'isAudio' => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['mp3', 'wav', 'ogg', 'm4a', 'aac'], true),
            ])
            ->values()
            ->all();
    }
}
