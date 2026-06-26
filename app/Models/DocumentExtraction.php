<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentExtraction extends Model
{
    protected $fillable = [
        'document_id',
        'vendor_name',
        'vendor_tax_id',
        'vendor_vat_number',
        'customer_name',
        'customer_tax_id',
        'document_number',
        'document_date',
        'due_date',
        'currency',
        'subtotal',
        'vat_amount',
        'total_amount',
        'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_confirmed' => 'boolean',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
