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

    /**
     * The hours of the shift, as shown beside its name.
     */
    public function getTimeRange(): string
    {
        return match ($this) {
            self::First => '7h30 - 9h30',
            self::Second => '9h30 - 11h30',
            self::Third => '14h - 16h',
            self::Fourth => '16h30 - 18h30',
            self::Fifth => '19h30 - 21h30',
        };
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
