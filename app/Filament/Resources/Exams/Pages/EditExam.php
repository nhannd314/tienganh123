<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use App\Models\Exam;
use App\Services\ExamPaper;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('Open exam page')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (Exam $record): string => route('exams.show', $record), shouldOpenInNewTab: true),
            Action::make('pdf')
                ->label('Tải đề thi (PDF)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (Exam $record) => response()->streamDownload(
                    fn () => print (Pdf::loadView('exams.pdf', [
                        'exam' => $record,
                        'sections' => app(ExamPaper::class)->sections($record),
                    ])->output()),
                    $record->slug.'.pdf',
                    ['Content-Type' => 'application/pdf'],
                )),
            DeleteAction::make(),
        ];
    }
}
