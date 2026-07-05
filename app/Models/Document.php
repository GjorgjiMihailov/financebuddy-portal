<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\IntakeChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'parent_document_id',
        'uploaded_by',
        'type',
        'status',
        'intake_channel',
        'original_filename',
        'storage_path',
        'drive_file_id',
        'mime_type',
        'file_size',
        'ai_raw_response',
        'ai_confidence',
        'ai_processed_at',
        'verified_by',
        'verified_at',
        'booked_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type'            => DocumentType::class,
            'status'          => DocumentStatus::class,
            'intake_channel'  => IntakeChannel::class,
            'ai_raw_response' => 'array',
            'ai_confidence'   => 'float',
            'ai_processed_at' => 'datetime',
            'verified_at'     => 'datetime',
            'booked_at'       => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function extraction(): HasOne
    {
        return $this->hasOne(DocumentExtraction::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(DocumentLineItem::class)->orderBy('sort_order');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function aiLogs(): HasMany
    {
        return $this->hasMany(AiProcessingLog::class);
    }

    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    public function childDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_document_id');
    }
}