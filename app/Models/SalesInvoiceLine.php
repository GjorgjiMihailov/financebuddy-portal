<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceLine extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'item_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'vat_rate',
        'line_total_ex_vat',
        'vat_amount',
        'line_total_inc_vat',
        'sort_order',
    ];

    protected $casts = [
        'quantity'          => 'decimal:3',
        'unit_price'        => 'decimal:4',
        'vat_rate'          => 'decimal:2',
        'line_total_ex_vat' => 'decimal:2',
        'vat_amount'        => 'decimal:2',
        'line_total_inc_vat'=> 'decimal:2',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
