<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case AiProcessing = 'ai_processing';
    case AiProcessed = 'ai_processed';
    case Verified = 'verified';
    case Booked = 'booked';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Чека обработка',
            self::AiProcessing => 'AI обработка во тек',
            self::AiProcessed => 'AI обработен',
            self::Verified => 'Верификуван',
            self::Booked => 'Прокнижен',
            self::Rejected => 'Одбиен',
        };
    }
}
