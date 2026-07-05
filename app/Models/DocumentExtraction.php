<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentExtraction extends Model
{
    protected $fillable = [
        'document_id',
        // Invoice fields
        'vendor_name', 'vendor_tax_id', 'vendor_vat_number',
        'customer_name', 'customer_tax_id',
        'document_number', 'document_date', 'due_date',
        'currency', 'subtotal', 'vat_amount', 'total_amount',
        'is_confirmed',
        // Bank statement fields
        'bank_name', 'account_number', 'statement_number',
        'opening_balance', 'closing_balance', 'total_debit', 'total_credit',
    ];

    protected function casts(): array
    {
        return [
            'document_date'   => 'date',
            'due_date'        => 'date',
            'subtotal'        => 'decimal:2',
            'vat_amount'      => 'decimal:2',
            'total_amount'    => 'decimal:2',
            'is_confirmed'    => 'boolean',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'total_debit'     => 'decimal:2',
            'total_credit'    => 'decimal:2',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}