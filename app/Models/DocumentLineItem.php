<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLineItem extends Model
{
    protected $fillable = [
        'document_id',
        'sort_order',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'vat_rate',
        'vat_amount',
        'total_amount',
        'suggested_account_code',
        'confirmed_account_code',
        'ai_confidence',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'ai_confidence' => 'float',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function suggestedAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'suggested_account_code', 'code');
    }

    public function confirmedAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'confirmed_account_code', 'code');
    }
}
