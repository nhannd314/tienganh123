<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\Vocabulary;
use App\Models\Word;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    /**
     * The kinds of result that can be searched, by the value of the `loai` query parameter.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'khoa-hoc' => 'Khóa học',
        'tu-vung' => 'Từ vựng',
        'flashcard' => 'Flashcard',
    ];

    private const LIMIT_ALL = 6;

    private const PER_PAGE = 12;

    /**
     * Search published courses, vocabularies and flashcard sets by what the visitor typed, ignoring case and accents.
     * Without a kind each kind shows a preview; with one the results are paginated.
     */
    public function __invoke(Request $request): View
    {
        $term = trim((string) $request->query('q'));
        $type = array_key_exists((string) $request->query('loai'), self::TYPES) ? (string) $request->query('loai') : null;
        $needle = $this->normalize($term);

        $empty = new Collection;
        $searches = fn (string $kind): bool => $needle !== '' && in_array($type, [null, $kind], true);

        return view('search.index', [
            'term' => $term,
            'type' => $type,
            'types' => self::TYPES,
            'courses' => $searches('khoa-hoc') ? $this->results($this->courses($needle), $type) : $empty,
            'vocabularies' => $searches('tu-vung') ? $this->results($this->vocabularies($needle), $type) : $empty,
            'flashcardSets' => $searches('flashcard') ? $this->results($this->flashcardSets($needle), $type) : $empty,
        ]);
    }

    /**
     * A preview of the results, or a page of them when the search is limited to one kind.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Collection<int, TModel>|LengthAwarePaginator<int, TModel>
     */
    private function results(Builder $query, ?string $type): Collection|LengthAwarePaginator
    {
        $query->orderBy('sort_order')->orderBy('id');

        return $type === null
            ? $query->limit(self::LIMIT_ALL)->get()
            : $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Published courses whose title or description matches.
     *
     * @return Builder<Course>
     */
    private function courses(string $needle): Builder
    {
        return Course::query()->whereKey(
            $this->matchingKeys(Course::query()->published(), ['title', 'description'], $needle)
        );
    }

    /**
     * Vocabularies whose title, or one of whose words, matches.
     *
     * @return Builder<Vocabulary>
     */
    private function vocabularies(string $needle): Builder
    {
        $titleKeys = $this->matchingKeys(Vocabulary::query(), ['title'], $needle);
        $wordKeys = $this->matchingKeys(Word::query(), ['word'], $needle);

        return Vocabulary::query()
            ->where(fn (Builder $query) => $query->whereKey($titleKeys)->orWhereHas('words', fn (Builder $words) => $words->whereKey($wordKeys)))
            ->withCount('words');
    }

    /**
     * Flashcard sets whose title, or one of whose cards, matches.
     *
     * @return Builder<FlashcardSet>
     */
    private function flashcardSets(string $needle): Builder
    {
        $titleKeys = $this->matchingKeys(FlashcardSet::query(), ['title'], $needle);
        $cardKeys = $this->matchingKeys(Flashcard::query(), ['title'], $needle);

        return FlashcardSet::query()
            ->where(fn (Builder $query) => $query->whereKey($titleKeys)->orWhereHas('flashcards', fn (Builder $cards) => $cards->whereKey($cardKeys)))
            ->withCount('flashcards');
    }

    /**
     * Keys of the records of the query where one of the columns contains the normalized needle. The comparison is done in
     * PHP so it does not depend on the collation of the database (utf8mb4_unicode_ci does not fold "đ" into "d").
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $columns
     * @return list<int|string>
     */
    private function matchingKeys(Builder $query, array $columns, string $needle): array
    {
        $key = $query->getModel()->getKeyName();

        return $query->get([$key, ...$columns])
            ->filter(fn (Model $model): bool => collect($columns)->contains(
                fn (string $column): bool => str_contains($this->normalize((string) $model->{$column}), $needle)
            ))
            ->modelKeys();
    }

    /**
     * Lowercase text without accents, so "Động vật" and "dong vat" compare equal.
     */
    private function normalize(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }
}
