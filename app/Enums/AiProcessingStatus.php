<?php

namespace App\Enums;

enum AiProcessingStatus: string
{
    case Success = 'success';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Успешно',
            self::Failed => 'Неуспешно',
        };
    }
}
