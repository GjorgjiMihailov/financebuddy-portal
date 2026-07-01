<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'embg',
        'position',
        'net_salary',
        'bank_account',
        'is_active',
        'hired_at',
    ];

    protected function casts(): array
    {
        return [
            'net_salary' => 'decimal:2',
            'is_active'  => 'boolean',
            'hired_at'   => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
