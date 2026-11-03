<?php

namespace App\Filament\Resources\Exams\RelationManagers;

use App\Models\ExamAttempt;
use App\Models\ExamGroup;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The results of the exam: who sat it, their class and their score. Attempts are only made by the students.
 */
class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $title = 'Results';

    protected static ?string $recordTitleAttribute = 'student_name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_name')
                    ->label('Student')
                    ->searchable(),
                TextColumn::make('class_name')
                    ->label('Class')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('score')
                    ->numeric(2)
                    ->placeholder('Chưa nộp')
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Account')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('class_name')
                    ->label('Class')
                    ->options(fn (): array => $this->getOwnerRecord()->attempts()->distinct()->orderBy('class_name')->pluck('class_name', 'class_name')->all()),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Chi tiết điểm')
                    ->icon('heroicon-o-list-bullet')
                    ->modalHeading(fn (ExamAttempt $record): string => 'Chi tiết điểm: '.$record->student_name)
                    ->modalContent(fn (ExamAttempt $record) => view('filament.exams.attempt-details', [
                        'attempt' => $record,
                        'groups' => $this->groupScores($record),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * The score of the attempt group by group, from its graded answers.
     *
     * @return list<array{title: string, total: int, correct: int, points: float}>
     */
    private function groupScores(ExamAttempt $attempt): array
    {
        $answersByGroup = collect($attempt->answers ?? [])->groupBy('group_id');

        return $this->getOwnerRecord()->groups()->get()
            ->map(fn (ExamGroup $group): array => [
                'title' => $group->title,
                'total' => $answersByGroup->get($group->id)?->count() ?? 0,
                'correct' => $answersByGroup->get($group->id)?->where('correct', true)->count() ?? 0,
                'points' => round((float) $answersByGroup->get($group->id)?->sum('points'), 2),
            ])
            ->filter(fn (array $group): bool => $group['total'] > 0)
            ->values()
            ->all();
    }
}
