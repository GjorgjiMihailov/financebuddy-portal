<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\JournalEntryStatus;
use App\Http\Requests\StoreJournalEntryRequest;
use App\Models\ChartOfAccount;
use App\Models\Document;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{

    public function index(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);

        $entries = JournalEntry::with(['document:id,original_filename', 'creator:id,name'])
            ->where('company_id', $companyId)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30);

        return Inertia::render('journal-entries/Index', [
            'entries' => $entries,
        ]);
    }
    public function create(Document $document): Response
    {
        $this->authorize('create', JournalEntry::class);

        abort_unless($document->status === DocumentStatus::Verified, 403, 'Документот мора да биде верификуван.');
        abort_if($document->journalEntries()->exists(), 409, 'Книжење за овoj документ веќе постои.');

        $document->load(['company:id,name', 'extraction', 'lineItems.suggestedAccount']);

        $prefillLines = $document->lineItems
            ->filter(fn ($item) => $item->confirmed_account_code || $item->suggested_account_code)
            ->map(fn ($item) => [
                'account_code' => $item->confirmed_account_code ?? $item->suggested_account_code,
                'description'  => $item->description,
                'debit'        => 0,
                'credit'       => 0,
            ])
            ->values();

        $accounts = ChartOfAccount::where('allows_posting', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name', 'class'])
            ->groupBy('class');

        return Inertia::render('journal-entries/Create', [
            'document'     => $document,
            'accounts'     => $accounts,
            'prefillLines' => $prefillLines,
        ]);
    }

    public function store(StoreJournalEntryRequest $request, Document $document): RedirectResponse
    {
        abort_unless($document->status === DocumentStatus::Verified, 403);
        abort_if($document->journalEntries()->exists(), 409);

        $entry = JournalEntry::create([
            'document_id' => $document->id,
            'company_id'  => $document->company_id,
            'entry_date'  => $request->entry_date,
            'description' => $request->description,
            'reference'   => $request->reference,
            'status'      => JournalEntryStatus::Draft,
            'created_by'  => $request->user()->id,
        ]);

        foreach ($request->lines as $i => $line) {
            $entry->lines()->create([
                'sort_order'   => $i,
                'account_code' => $line['account_code'],
                'debit'        => $line['debit'],
                'credit'       => $line['credit'],
                'description'  => $line['description'] ?? null,
            ]);
        }

        if ($request->boolean('post_immediately')) {
            $this->doPost($entry, $document, $request->user()->id);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Книжењето е прокнижено.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Нацртот е зачуван.']);
        }

        return to_route('journal-entries.show', $entry);
    }

    public function show(JournalEntry $journalEntry): Response
    {
        $this->authorize('view', $journalEntry);

        $journalEntry->load(['company:id,name', 'document:id,original_filename', 'creator:id,name', 'poster:id,name', 'lines.account:code,name']);

        return Inertia::render('journal-entries/Show', [
            'entry' => $journalEntry,
        ]);
    }

    public function post(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('post', $journalEntry);

        $this->doPost($journalEntry, $journalEntry->document, $request->user()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Книжењето е прокнижено.']);

        return to_route('journal-entries.show', $journalEntry);
    }

    private function doPost(JournalEntry $entry, ?Document $document, int $userId): void
    {
        $entry->update([
            'status'    => JournalEntryStatus::Posted,
            'posted_by' => $userId,
            'posted_at' => now(),
        ]);

        $document?->update(['status' => DocumentStatus::Booked]);
    }
}
