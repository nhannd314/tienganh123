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
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $lesson = parent::handleRecordCreation(Arr::except($data, LessonForm::CONTENT_LINES_FIELDS));

        LessonForm::createContentSets($lesson, Arr::only($data, LessonForm::CONTENT_LINES_FIELDS));

        return $lesson;
    }
}
