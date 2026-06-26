<?php

namespace App\Enums;

enum IntakeChannel: string
{
    case Portal = 'portal';
    case Email = 'email';
    case Telegram = 'telegram';
    case WhatsApp = 'whatsapp';
    case Viber = 'viber';

    public function label(): string
    {
        return match ($this) {
            self::Portal => 'Портал',
            self::Email => 'Е-пошта',
            self::Telegram => 'Telegram',
            self::WhatsApp => 'WhatsApp',
            self::Viber => 'Viber',
        };
    }
}
