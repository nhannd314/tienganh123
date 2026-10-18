<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\Schemas\LessonForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    /**
     * The example sentences as they were before the save, to find the images the save dropped.
     *
     * @var array<int, mixed>
     */
    protected array $previousExampleSentences = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return LessonForm::fillVideoFields($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return LessonForm::keepSentenceAudio(LessonForm::mergeVideoFields($data), $this->getRecord());
    }

    protected function beforeSave(): void
    {
        $this->previousExampleSentences = $this->getRecord()->example_sentences ?? [];
    }

    protected function afterSave(): void
    {
        LessonForm::deleteDroppedSentenceImages($this->previousExampleSentences, $this->getRecord());
        LessonForm::queueMissingSentenceAudio($this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
