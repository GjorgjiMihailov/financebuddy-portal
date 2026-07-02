<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'is_service',
        'is_macedonian',
    ];

    protected function casts(): array
    {
        return [
            'price_without_vat' => 'decimal:2',
            'is_active'         => 'boolean',
            'is_service'        => 'boolean',
            'is_macedonian'     => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(WarehouseMovement::class);
    }
}
