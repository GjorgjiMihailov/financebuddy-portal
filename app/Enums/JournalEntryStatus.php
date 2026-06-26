<?php

namespace App\Enums;

enum JournalEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Нацрт',
            self::Posted => 'Прокнижено',
        };
    }
}
