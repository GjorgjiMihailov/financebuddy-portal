<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document) {}

    public function handle(): void
    {
        // TODO: Claude API integration (Фаза 2)
        $this->document->update(['status' => DocumentStatus::AiProcessing]);
    }
}
