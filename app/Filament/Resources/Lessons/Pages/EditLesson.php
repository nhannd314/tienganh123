<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\Schemas\LessonForm;
use App\Models\Lesson;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    /**
     * Saving the lesson and the sets made from its lines stands or falls together, so a failure leaves no set behind.
     */
    protected ?bool $hasDatabaseTransactions = true;

    /**
     * Whether saving created a vocabulary, flashcard set or sentence set, which the page reloads to list.
     */
    private bool $hasCreatedContentSets = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('Open lesson page')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->visible(fn (Lesson $record): bool => $record->is_published && $record->unit->course->is_published)
                ->url(fn (Lesson $record): string => route('lessons.show', [$record->unit->course, $record->unit, $record]), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  Lesson  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $lesson = parent::handleRecordUpdate($record, Arr::except($data, LessonForm::CONTENT_LINES_FIELDS));

        $this->hasCreatedContentSets = LessonForm::createContentSets($lesson, Arr::only($data, LessonForm::CONTENT_LINES_FIELDS));

        return $lesson;
    }

    /**
     * Reload the page after new sets were created, so the relation managers list them and the filled fields hide.
     */
    protected function getRedirectUrl(): ?string
    {
        return $this->hasCreatedContentSets ? $this->getResourceUrl('edit') : parent::getRedirectUrl();
    }
}
