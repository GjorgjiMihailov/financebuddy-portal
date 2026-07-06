<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'warehouse_id',
        'kooperant_id',
        'document_id',
        'supplier_name',
        'invoice_number',
        'date',
        'due_date',
        'notes',
        'subtotal',
        'vat_total',
        'total_amount',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date'         => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'decimal:2',
        'vat_total'    => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function kooperant(): BelongsTo
    {
        return $this->belongsTo(Kooperant::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceLine::class)->orderBy('sort_order');
    }
}
