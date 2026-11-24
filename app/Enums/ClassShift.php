<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ClassShift: int implements HasLabel
{
    case First = 1;
    case Second = 2;
    case Third = 3;
    case Fourth = 4;
    case Fifth = 5;

    public function getLabel(): string
    {
        return 'Ca '.$this->value;
    }

    public function getPeriodLabel(): string
    {
        return match ($this) {
            self::First, self::Second => 'Sáng',
            self::Third, self::Fourth => 'Chiều',
            self::Fifth => 'Tối',
        };
    }
}
