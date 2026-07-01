<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kontragent extends Model
{
    protected $table = 'kontragenti';

    protected $fillable = [
        'company_id',
        'name',
        'edb',
        'embs',
        'address',
        'phone',
        'email',
        'is_vat_payer',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_vat_payer' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
