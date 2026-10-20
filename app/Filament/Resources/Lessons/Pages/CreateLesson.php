<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\Schemas\LessonForm;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    /**
     * Fields of the form that are not lesson columns: the lines saved as the vocabulary, flashcard set and sentence set of the lesson.
     *
     * @var list<string>
     */
    private const CONTENT_LINES_FIELDS = ['vocabulary_lines', 'flashcard_lines', 'sentence_lines', 'generate_sentence_images'];

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $lesson = parent::handleRecordCreation(Arr::except($data, self::CONTENT_LINES_FIELDS));

        LessonForm::createContentSets($lesson, Arr::only($data, self::CONTENT_LINES_FIELDS));

        return $lesson;
    }
}
