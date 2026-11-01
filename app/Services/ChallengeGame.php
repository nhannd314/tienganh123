<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The brick-throwing game of an accepted challenge. The server is the referee: both players press Start, then type the
 * current word letter by letter from its Vietnamese meaning. Every right letter throws a brick at the other player; who
 * finishes the word first wins the round, the other loses one health, and the next word comes up. The game ends when a
 * player has no health left, or when the words run out: the one with less health loses (the same health is a draw).
 *
 * Each player starts with HEALTH_SHARE of the number of words, at least 1 (10 words give 7 health).
 * Every change runs with the challenge row locked, so two last letters typed at the same moment cannot both win a round.
 */
class ChallengeGame
{
    public const HEALTH_SHARE = 0.7;

    /**
     * The player pressed Start. Once both have, the game starts with the words of the lesson in a random order.
     *
     * @return array{state: array<string, mixed>, event: string|null}
     */
    public function ready(Challenge $challenge, User $user): array
    {
        return $this->locked($challenge, function (Challenge $challenge) use ($user): ?string {
            if ($challenge->started_at !== null) {
                return null;
            }

            $challenge->{$challenge->sideOf($user).'_ready_at'} ??= now();

            if ($challenge->challenger_ready_at === null || $challenge->opponent_ready_at === null) {
                $challenge->save();

                return 'ready';
            }

            $words = $this->wordsOf($challenge);
            $health = max(1, (int) round(count($words) * self::HEALTH_SHARE));

            $challenge->fill([
                'started_at' => now(),
                'words' => $words,
                'word_index' => 0,
                'max_health' => $health,
                'challenger_health' => $health,
                'opponent_health' => $health,
                'challenger_progress' => 0,
                'opponent_progress' => 0,
            ]);

            if ($words === []) {
                $challenge->finished_at = now();
            }

            $challenge->save();

            return $words === [] ? 'finish' : 'start';
        });
    }

    /**
     * The player typed a letter of the word at `$wordIndex`. Only the next letter of that word counts (accents and case
     * do not matter); anything else changes nothing.
     *
     * @return array{state: array<string, mixed>, event: string|null}
     */
    public function type(Challenge $challenge, User $user, int $wordIndex, string $letter): array
    {
        return $this->locked($challenge, function (Challenge $challenge) use ($user, $wordIndex, $letter): ?string {
            if (! $this->isPlaying($challenge) || $wordIndex !== $challenge->word_index) {
                return null;
            }

            $side = $challenge->sideOf($user);
            $letters = self::lettersOf($challenge->words[$challenge->word_index]['word']);
            $progress = $challenge->{$side.'_progress'};

            if (self::plain($letter) !== self::plain($letters[$progress])) {
                return null;
            }

            $challenge->{$side.'_progress'} = $progress + 1;

            if ($progress + 1 < count($letters)) {
                $challenge->save();

                return 'letter';
            }

            $this->winRound($challenge, $user);
            $challenge->save();

            return $challenge->finished_at !== null ? 'finish' : 'round';
        });
    }

    /**
     * What both players see of the game. The current word is sent with its meaning, so the page shows its letters as
     * they are typed.
     *
     * @return array{status: string, ready: array{challenger: bool, opponent: bool}, maxHealth: int|null, health: array{challenger: int|null, opponent: int|null}, progress: array{challenger: int, opponent: int}, wordIndex: int, wordCount: int, word: array{word: string, meaning: string}|null, winner: string|null}
     */
    public function state(Challenge $challenge): array
    {
        $words = $challenge->words ?? [];

        return [
            'status' => match (true) {
                $challenge->finished_at !== null => 'finished',
                $challenge->started_at !== null => 'playing',
                default => 'waiting',
            },
            'ready' => ['challenger' => $challenge->challenger_ready_at !== null, 'opponent' => $challenge->opponent_ready_at !== null],
            'maxHealth' => $challenge->max_health,
            'health' => ['challenger' => $challenge->challenger_health, 'opponent' => $challenge->opponent_health],
            'progress' => ['challenger' => $challenge->challenger_progress, 'opponent' => $challenge->opponent_progress],
            'wordIndex' => $challenge->word_index,
            'wordCount' => count($words),
            'word' => $this->isPlaying($challenge) ? $words[$challenge->word_index] : null,
            'winner' => match ($challenge->winner_id) {
                null => null,
                $challenge->challenger_id => 'challenger',
                default => 'opponent',
            },
        ];
    }

    /**
     * The letters a player types for the word: its letters and digits, without spaces, hyphens or apostrophes.
     *
     * @return list<string>
     */
    public static function lettersOf(string $word): array
    {
        preg_match_all('/[\p{L}\p{N}]/u', $word, $matches);

        return $matches[0];
    }

    /**
     * The letter without accents and in lower case, so a Vietnamese keyboard (Telex turns "ee" into "ê") still types the right letter.
     */
    private static function plain(string $letter): string
    {
        return Str::lower(Str::ascii($letter));
    }

    /**
     * The player finished the word first: the other one loses a health and the next word comes up, unless the game is over.
     */
    private function winRound(Challenge $challenge, User $winner): void
    {
        $loserSide = $challenge->sideOf($winner) === 'challenger' ? 'opponent' : 'challenger';

        $challenge->{$loserSide.'_health'} = max(0, $challenge->{$loserSide.'_health'} - 1);
        $challenge->word_index += 1;
        $challenge->challenger_progress = 0;
        $challenge->opponent_progress = 0;

        if ($challenge->{$loserSide.'_health'} > 0 && $challenge->word_index < count($challenge->words)) {
            return;
        }

        $challenge->finished_at = now();
        $challenge->winner_id = match ($challenge->challenger_health <=> $challenge->opponent_health) {
            1 => $challenge->challenger_id,
            -1 => $challenge->opponent_id,
            0 => null,
        };
    }

    private function isPlaying(Challenge $challenge): bool
    {
        return $challenge->started_at !== null && $challenge->finished_at === null;
    }

    /**
     * The words of the lesson that have letters to type, each with its meanings, in a random order.
     *
     * @return list<array{word: string, meaning: string}>
     */
    private function wordsOf(Challenge $challenge): array
    {
        return $challenge->lesson->reviewWords()
            ->filter(fn (Word $word): bool => self::lettersOf($word->word) !== [])
            ->shuffle()
            ->map(fn (Word $word): array => ['word' => $word->word, 'meaning' => $word->plainMeaningsLabel()])
            ->values()
            ->all();
    }

    /**
     * Run the change with the challenge row locked and return the new state with what happened (null for nothing).
     *
     * @param  callable(Challenge): ?string  $change
     * @return array{state: array<string, mixed>, event: string|null}
     */
    private function locked(Challenge $challenge, callable $change): array
    {
        return DB::transaction(function () use ($challenge, $change): array {
            $fresh = Challenge::query()->lockForUpdate()->findOrFail($challenge->id);
            $event = $change($fresh);
            $challenge->setRawAttributes($fresh->getAttributes(), true);

            return ['state' => $this->state($fresh), 'event' => $event];
        });
    }
}
