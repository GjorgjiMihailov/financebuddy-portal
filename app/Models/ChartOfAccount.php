<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'class',
        'account_type',
        'parent_code',
        'allows_posting',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'class' => 'integer',
            'account_type' => AccountType::class,
            'allows_posting' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_code', 'code');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_code', 'code');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_code', 'code');
    }
}
