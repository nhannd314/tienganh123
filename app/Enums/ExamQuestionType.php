<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExamQuestionType: string implements HasLabel
{
    case Choice = 'choice';
    case Fill = 'fill';

    public function getLabel(): string
    {
        return match ($this) {
            self::Choice => 'Chọn đáp án đúng',
            self::Fill => 'Điền từ',
        };
    }
}
