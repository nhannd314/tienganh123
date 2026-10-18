<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Membership: string implements HasLabel
{
    case Member = 'member';

    public function getLabel(): string
    {
        return match ($this) {
            self::Member => 'Thành viên',
        };
    }
}
