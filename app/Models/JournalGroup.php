<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalGroup extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['code', 'name', 'description'];

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'group_code', 'code');
    }
}