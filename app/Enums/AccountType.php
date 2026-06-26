<?php

namespace App\Enums;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';
    case CostCenter = 'cost_center';
    case BankOnly = 'bank_only';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Средство',
            self::Liability => 'Обврска',
            self::Equity => 'Капитал',
            self::Revenue => 'Приход',
            self::Expense => 'Расход / Трошок',
            self::CostCenter => 'Трошковен центар',
            self::BankOnly => 'Само за банки',
        };
    }
}
