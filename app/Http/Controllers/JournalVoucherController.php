<?php

namespace App\Http\Controllers;

use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;
use App\Models\JournalGroup;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JournalVoucherController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);
        $year      = (int) ($request->query('year', date('Y')));
        $groupCode = $request->query('group');

        $groups = JournalGroup::orderBy('code')->get()->map(function ($g) use ($companyId, $year) {
            $g->entry_count = JournalEntry::where('company_id', $companyId)
                ->where('group_code', $g->code)
                ->where('year', $year)
                ->count();
            return $g;
        });

        if (! $groupCode) {
            $groupCode = $groups->first(fn ($g) => $g->entry_count > 0)?->code
                ?? $groups->first()?->code;
        }

        $entry    = null;
        $total    = 0;
        $position = 0;

        if ($groupCode !== null) {
            $gc = (int) $groupCode;

            $seq   = $request->query('seq');
            $entry = $seq
                ? $this->baseQuery($companyId, $gc, $year)->where('sequence_number', $seq)->first()
                : $this->baseQuery($companyId, $gc, $year)->orderBy('sequence_number')->first();

            if ($entry) {
                $entry->load([
                    'lines.account:code,name,class,account_type',
                    'lines.kontragent:id,name,edb,embs,address,phone,email',
                    'journalGroup:code,name',
                ]);
                $total    = $this->baseQuery($companyId, $gc, $year)->count();
                $position = $this->baseQuery($companyId, $gc, $year)
                    ->where('sequence_number', '<=', $entry->sequence_number)->count();
            }
        }

        return Inertia::render('journal-entries/Voucher', [
            'groups'       => $groups,
            'initialEntry' => $entry,
            'groupCode'    => (int) $groupCode,
            'year'         => $year,
            'companyId'    => $companyId,
            'position'     => $position,
            'total'        => $total,
        ]);
    }

    public function navigate(Request $request): JsonResponse
    {
        $direction  = (string) $request->query('direction', 'first');
        $groupCode  = (int) $request->query('group_code');
        $year       = (int) $request->query('year', date('Y'));
        $companyId  = (int) $request->query('company_id');
        $currentSeq = $request->query('current_seq') ? (int) $request->query('current_seq') : null;

        $total = $this->baseQuery($companyId, $groupCode, $year)->count();

        $entry = match ($direction) {
            'first' => $this->baseQuery($companyId, $groupCode, $year)->orderBy('sequence_number')->first(),
            'last'  => $this->baseQuery($companyId, $groupCode, $year)->orderByDesc('sequence_number')->first(),
            'prev'  => $currentSeq
                ? $this->baseQuery($companyId, $groupCode, $year)
                    ->where('sequence_number', '<', $currentSeq)->orderByDesc('sequence_number')->first()
                : null,
            'next'  => $currentSeq
                ? $this->baseQuery($companyId, $groupCode, $year)
                    ->where('sequence_number', '>', $currentSeq)->orderBy('sequence_number')->first()
                : null,
            default => null,
        };

        if (! $entry) {
            return response()->json(['entry' => null, 'total' => $total, 'position' => 0]);
        }

        $entry->load([
            'lines.account:code,name,class,account_type',
            'lines.kontragent:id,name,edb,embs,address,phone,email',
            'journalGroup:code,name',
        ]);

        $position = $this->baseQuery($companyId, $groupCode, $year)
            ->where('sequence_number', '<=', $entry->sequence_number)->count();

        return response()->json([
            'entry'    => $entry,
            'total'    => $total,
            'position' => $position,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $companyId = $this->currentCompanyId($request);
        $validated = $this->validateVoucher($request);

        return DB::transaction(function () use ($validated, $companyId, $request) {
            $seq = $this->nextSeq($validated['group_code'], $validated['year'], $companyId);

            $entry = JournalEntry::create([
                'company_id'      => $companyId,
                'group_code'      => $validated['group_code'],
                'year'            => $validated['year'],
                'sequence_number' => $seq,
                'entry_date'      => $validated['entry_date'],
                'description'     => $validated['description'],
                'reference'       => $validated['reference'] ?? null,
                'status'          => JournalEntryStatus::Draft,
                'created_by'      => $request->user()->id,
            ]);

            $this->syncLines($entry, $validated['lines']);

            return $this->entryResponse($entry, $companyId);
        });
    }

    public function update(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        $companyId = $this->currentCompanyId($request);
        abort_unless($journalEntry->company_id === $companyId, 403);

        if ($journalEntry->status === JournalEntryStatus::Posted) {
            return response()->json(['error' => 'Прокнижен налог не може да се менува.'], 422);
        }

        $validated = $this->validateVoucher($request);

        return DB::transaction(function () use ($validated, $journalEntry, $companyId) {
            $journalEntry->update([
                'entry_date'  => $validated['entry_date'],
                'description' => $validated['description'],
                'reference'   => $validated['reference'] ?? null,
            ]);

            $journalEntry->lines()->delete();
            $this->syncLines($journalEntry, $validated['lines']);

            return $this->entryResponse($journalEntry, $companyId);
        });
    }

    public function destroy(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        $companyId = $this->currentCompanyId($request);
        abort_unless($journalEntry->company_id === $companyId, 403);

        if ($journalEntry->status === JournalEntryStatus::Posted) {
            return response()->json(['error' => 'Не може да се избрише прокнижен налог.'], 422);
        }

        $groupCode = $journalEntry->group_code;
        $year      = $journalEntry->year;

        $journalEntry->delete();

        $next  = $this->baseQuery($companyId, $groupCode, $year)->orderBy('sequence_number')->first();
        $total = $this->baseQuery($companyId, $groupCode, $year)->count();

        if ($next) {
            $next->load([
                'lines.account:code,name,class,account_type',
                'lines.kontragent:id,name,edb,embs,address,phone,email',
                'journalGroup:code,name',
            ]);
        }

        $position = $next
            ? $this->baseQuery($companyId, $groupCode, $year)->where('sequence_number', '<=', $next->sequence_number)->count()
            : 0;

        return response()->json([
            'entry'    => $next,
            'total'    => $total,
            'position' => $position,
            'message'  => 'Налогот е избришан.',
        ]);
    }

    public function openInvoices(Request $request): JsonResponse
    {
        $kontragentId = (int) $request->query('kontragent_id');
        $companyId    = (int) $request->query('company_id');

        $purchases = PurchaseInvoice::where('company_id', $companyId)
            ->where('kontragent_id', $kontragentId)
            ->where('status', '!=', 'draft')
            ->orderByDesc('date')
            ->limit(10)
            ->get(['id', 'invoice_number', 'date', 'total_amount', 'status']);

        $sales = SalesInvoice::where('company_id', $companyId)
            ->where('kontragent_id', $kontragentId)
            ->where('status', '!=', 'draft')
            ->orderByDesc('date')
            ->limit(10)
            ->get(['id', 'invoice_number', 'date', 'total_amount', 'status']);

        return response()->json([
            'purchases' => $purchases,
            'sales'     => $sales,
        ]);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function baseQuery(int $companyId, int $groupCode, int $year): Builder
    {
        return JournalEntry::where('company_id', $companyId)
            ->where('group_code', $groupCode)
            ->where('year', $year);
    }

    private function validateVoucher(Request $request): array
    {
        return $request->validate([
            'group_code'                => ['required', 'integer', 'exists:journal_groups,code'],
            'year'                      => ['required', 'integer'],
            'entry_date'                => ['required', 'date'],
            'description'               => ['required', 'string', 'max:500'],
            'reference'                 => ['nullable', 'string', 'max:100'],
            'lines'                     => ['required', 'array', 'min:1'],
            'lines.*.account_code'      => ['required', 'string', 'exists:chart_of_accounts,code'],
            'lines.*.kontragent_id'     => ['nullable', 'integer', 'exists:kontragenti,id'],
            'lines.*.line_date'         => ['nullable', 'date'],
            'lines.*.description'       => ['nullable', 'string', 'max:255'],
            'lines.*.closing_reference' => ['nullable', 'string', 'max:100'],
            'lines.*.debit'             => ['required', 'numeric', 'min:0'],
            'lines.*.credit'            => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function syncLines(JournalEntry $entry, array $lines): void
    {
        foreach ($lines as $i => $line) {
            $entry->lines()->create([
                'sort_order'        => $i,
                'account_code'      => $line['account_code'],
                'kontragent_id'     => $line['kontragent_id'] ?? null,
                'line_date'         => $line['line_date'] ?? null,
                'description'       => $line['description'] ?? null,
                'closing_reference' => $line['closing_reference'] ?? null,
                'debit'             => $line['debit'],
                'credit'            => $line['credit'],
            ]);
        }
    }

    private function nextSeq(int $groupCode, int $year, int $companyId): int
    {
        return (JournalEntry::where('group_code', $groupCode)
            ->where('year', $year)
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->max('sequence_number') ?? 0) + 1;
    }

    private function entryResponse(JournalEntry $entry, int $companyId): JsonResponse
    {
        $entry->load([
            'lines.account:code,name,class,account_type',
            'lines.kontragent:id,name,edb,embs,address,phone,email',
            'journalGroup:code,name',
        ]);

        $total    = $this->baseQuery($companyId, $entry->group_code, $entry->year)->count();
        $position = $this->baseQuery($companyId, $entry->group_code, $entry->year)
            ->where('sequence_number', '<=', $entry->sequence_number)->count();

        return response()->json([
            'entry'    => $entry,
            'total'    => $total,
            'position' => $position,
            'message'  => 'Зачувано.',
        ]);
    }
}