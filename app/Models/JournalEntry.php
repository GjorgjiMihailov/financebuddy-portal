<?php

namespace App\Models;

use App\Enums\JournalEntryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable = [
        'document_id',
        'company_id',
        'entry_date',
        'description',
        'reference',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => JournalEntryStatus::class,
            'entry_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('sort_order');
    }
}
