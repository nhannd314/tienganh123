<?php

namespace App\Models;

use App\Enums\ExamQuestionType;
use Database\Factories\ExamQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;

/**
 * A question written by hand: a choice among `options`, `answer` being the number of the right one (from 1), or a word
 * to fill in, `answer` listing the accepted answers separated by ANSWER_SEPARATOR. In the prompt and the options, the
 * part wrapped in backticks (`a`pple) is shown bold and underlined.
 */
#[Fillable(['exam_group_id', 'type', 'prompt', 'options', 'answer', 'sort_order'])]
class ExamQuestion extends Model
{
    /** @use HasFactory<ExamQuestionFactory> */
    use HasFactory;

    public const ANSWER_SEPARATOR = '|';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ExamQuestionType::class,
            'options' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ExamGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ExamGroup::class, 'exam_group_id');
    }

    /**
     * The text as HTML, the parts wrapped in backticks shown bold and underlined: "`a`pple" becomes "<strong><u>a</u></strong>pple".
     */
    public static function markedHtml(string $text): HtmlString
    {
        return new HtmlString((string) preg_replace('/`([^`]+)`/u', '<strong><u>$1</u></strong>', e($text)));
    }

    /**
     * The answers taken as right: the number of the right option of a choice, or every accepted answer of a word to fill in.
     *
     * @return list<string>
     */
    public function acceptedAnswers(): array
    {
        if ($this->type === ExamQuestionType::Choice) {
            return [$this->answer];
        }

        return array_values(array_filter(array_map('trim', explode(self::ANSWER_SEPARATOR, $this->answer)), fn (string $answer): bool => $answer !== ''));
    }
}
