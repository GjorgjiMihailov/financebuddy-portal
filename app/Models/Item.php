<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'unit',
        'vat_category',
        'price_without_vat',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_without_vat' => 'decimal:2',
            'is_active'         => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
