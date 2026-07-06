<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'document_id',
        'warehouse_id',
        'kooperant_id',
        'client_name',
        'invoice_number',
        'date',
        'date_of_supply',
        'due_date',
        'notes',
        'subtotal',
        'vat_total',
        'total_amount',
        'currency',
        'exchange_rate',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date'            => 'date',
        'date_of_supply'  => 'date',
        'due_date'        => 'date',
        'subtotal'        => 'decimal:2',
        'vat_total'       => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'exchange_rate'   => 'decimal:4',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function kooperant(): BelongsTo
    {
        return $this->belongsTo(Kooperant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class)->orderBy('sort_order');
    }
}
