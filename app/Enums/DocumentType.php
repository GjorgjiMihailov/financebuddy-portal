<?php

namespace App\Enums;

enum DocumentType: string
{
    case InvoiceIn = 'invoice_in';
    case InvoiceOut = 'invoice_out';
    case BankStatement = 'bank_statement';
    case Contract = 'contract';
    case Receipt = 'receipt';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::InvoiceIn => 'Влезна фактура',
            self::InvoiceOut => 'Излезна фактура',
            self::BankStatement => 'Банкарски извод',
            self::Contract => 'Договор',
            self::Receipt => 'Сметка / Уплатница',
            self::Other => 'Друго',
        };
    }
}
