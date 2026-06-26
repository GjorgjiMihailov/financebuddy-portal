<?php

namespace App\Models;

use App\Enums\AiProcessingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProcessingLog extends Model
{
    protected $fillable = [
        'document_id',
        'model',
        'input_tokens',
        'output_tokens',
        'cost_usd',
        'duration_ms',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => AiProcessingStatus::class,
            'cost_usd' => 'decimal:6',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
