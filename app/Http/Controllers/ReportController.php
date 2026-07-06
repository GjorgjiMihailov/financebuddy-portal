<?php

namespace App\Http\Controllers;

use App\Enums\JournalEntryStatus;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Kooperant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function grossBalance(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);
        [$from, $to] = $this->periodParams($request);

        $accounts = $this->accountBalances($companyId, $from, $to)
            ->filter(fn ($r) => $this->hasActivity($r))
            ->map(fn ($r) => array_merge(
                ['code' => $r->account_code, 'name' => $r->account_name, 'class' => (int) $r->class],
                $this->shapeBucket((array) $r)
            ))
            ->values();

        return Inertia::render('reports/GrossBalance', [
            'company' => $this->reportCompany($companyId),
            'from' => $from,
            'to' => $to,
            'classes' => $this->groupByClass($accounts),
            'grandTotal' => $this->sumBuckets($accounts),
        ]);
    }

    public function grossBalanceByCompany(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);
        [$from, $to] = $this->periodParams($request);

        $rows = $this->accountCompanyBalances($companyId, $from, $to)
            ->filter(fn ($r) => $this->hasActivity($r));

        $accounts = $rows->groupBy('account_code')->map(function ($items, $code) {
            $first = $items->first();
            $companies = $items->map(fn ($r) => array_merge(
                ['kooperant_id' => $r->kooperant_id, 'name' => $r->kooperant_name ?? 'Без партнер'],
                $this->shapeBucket((array) $r)
            ))->values();

            return array_merge(
                ['code' => $code, 'name' => $first->account_name, 'class' => (int) $first->class, 'companies' => $companies],
                $this->sumBuckets($companies)
            );
        })->values();

        return Inertia::render('reports/GrossBalanceByCompany', [
            'company' => $this->reportCompany($companyId),
            'from' => $from,
            'to' => $to,
            'classes' => $this->groupByClass($accounts),
            'grandTotal' => $this->sumBuckets($accounts),
        ]);
    }

    public function grossBalanceSynthetic(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);
        [$from, $to] = $this->periodParams($request);

        $rows = $this->accountBalances($companyId, $from, $to)
            ->filter(fn ($r) => $this->hasActivity($r));

        $syntheticCodes = $rows->map(fn ($r) => substr($r->account_code, 0, 3))->unique()->values();
        $syntheticNames = ChartOfAccount::whereIn('code', $syntheticCodes)->pluck('name', 'code');

        $synthetics = $rows->groupBy(fn ($r) => substr($r->account_code, 0, 3))
            ->map(function ($items, $code) use ($syntheticNames) {
                $first = $items->first();
                $shaped = $items->map(fn ($r) => $this->shapeBucket((array) $r));

                return array_merge(
                    ['code' => $code, 'name' => $syntheticNames[$code] ?? $first->account_name, 'class' => (int) $first->class],
                    $this->sumBuckets($shaped)
                );
            })->values();

        return Inertia::render('reports/GrossBalanceSynthetic', [
            'company' => $this->reportCompany($companyId),
            'from' => $from,
            'to' => $to,
            'classes' => $this->groupByClass($synthetics),
            'grandTotal' => $this->sumBuckets($synthetics),
        ]);
    }

    public function cumulative(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);
        [$from, $to] = $this->periodParams($request);

        $rows = $this->accountCompanyBalances($companyId, $from, $to)
            ->filter(fn ($r) => (float) $r->period_debit !== 0.0 || (float) $r->period_credit !== 0.0);

        $accounts = $rows->groupBy('account_code')->map(function ($items, $code) {
            $first = $items->first();
            $companies = $items->map(function ($r) {
                $debit = (float) $r->period_debit;
                $credit = (float) $r->period_credit;

                return [
                    'kooperant_id' => $r->kooperant_id,
                    'name' => $r->kooperant_name ?? 'Без партнер',
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $debit - $credit,
                ];
            })->values();

            $totalDebit = $companies->sum('debit');
            $totalCredit = $companies->sum('credit');

            return [
                'code' => $code,
                'name' => $first->account_name,
                'companies' => $companies,
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'balance' => $totalDebit - $totalCredit,
            ];
        })->values();

        return Inertia::render('reports/Cumulative', [
            'company' => $this->reportCompany($companyId),
            'from' => $from,
            'to' => $to,
            'accounts' => $accounts,
            'grandTotal' => [
                'debit' => $accounts->sum('debit'),
                'credit' => $accounts->sum('credit'),
            ],
        ]);
    }

    public function ledgerAccountCompany(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);
        [$from, $to] = $this->periodParams($request);

        $accountCode = $request->query('account_code');
        $kooperantId = $request->query('kooperant_id');

        $account = $accountCode ? ChartOfAccount::find($accountCode) : null;
        $kooperant = $kooperantId ? Kooperant::find($kooperantId) : null;

        $rows = null;
        $opening = null;
        $total = null;

        if ($account && $kooperant) {
            $scope = fn ($q) => $q->where('jel.account_code', $accountCode)->where('jel.kooperant_id', $kooperantId);
            $opening = $this->ledgerOpening($companyId, $from, $scope);
            $lines = $this->ledgerLines($companyId, $from, $to, $scope);
            $rows = $this->buildLedgerRows($lines, $opening);
            $total = $this->rowTotals($rows);
        }

        return Inertia::render('reports/LedgerAccountCompany', [
            'company' => $this->reportCompany($companyId),
            'from' => $from,
            'to' => $to,
            'accountCode' => $accountCode,
            'account' => $account,
            'kooperantId' => $kooperantId ? (int) $kooperantId : null,
            'kooperant' => $kooperant,
            'opening' => $opening,
            'rows' => $rows,
            'total' => $total,
        ]);
    }

    public function ledgerAccount(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);
        [$from, $to] = $this->periodParams($request);

        $accountCode = $request->query('account_code');
        $account = $accountCode ? ChartOfAccount::find($accountCode) : null;

        $rows = null;
        $opening = null;
        $total = null;

        if ($account) {
            $scope = fn ($q) => $q->where('jel.account_code', $accountCode);
            $opening = $this->ledgerOpening($companyId, $from, $scope);
            $lines = $this->ledgerLines($companyId, $from, $to, $scope);
            $rows = $this->buildLedgerRows($lines, $opening);
            $total = $this->rowTotals($rows);
        }

        return Inertia::render('reports/LedgerAccount', [
            'company' => $this->reportCompany($companyId),
            'from' => $from,
            'to' => $to,
            'accountCode' => $accountCode,
            'account' => $account,
            'opening' => $opening,
            'rows' => $rows,
            'total' => $total,
        ]);
    }

    public function ledgerCompany(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);
        [$from, $to] = $this->periodParams($request);

        $kooperantId = $request->query('kooperant_id');
        $kooperant = $kooperantId ? Kooperant::find($kooperantId) : null;

        $sections = null;
        $grandTotal = null;

        if ($kooperant) {
            $scope = fn ($q) => $q->where('jel.kooperant_id', $kooperantId);
            $lines = $this->ledgerLines($companyId, $from, $to, $scope)->groupBy('account_code');

            $accountCodes = $lines->keys()->filter()->values();
            $accountNames = ChartOfAccount::whereIn('code', $accountCodes)->pluck('name', 'code');

            $sections = $accountCodes->map(function ($code) use ($lines, $accountNames, $companyId, $from, $kooperantId) {
                $accScope = fn ($q) => $q->where('jel.account_code', $code)->where('jel.kooperant_id', $kooperantId);
                $opening = $this->ledgerOpening($companyId, $from, $accScope);
                $rows = $this->buildLedgerRows($lines[$code], $opening);

                return [
                    'code' => $code,
                    'name' => $accountNames[$code] ?? $code,
                    'opening' => $opening,
                    'rows' => $rows,
                    'total' => $this->rowTotals($rows),
                ];
            })->values();

            $grandTotal = [
                'debit' => $sections->sum(fn ($s) => $s['total']['debit']),
                'credit' => $sections->sum(fn ($s) => $s['total']['credit']),
            ];
        }

        return Inertia::render('reports/LedgerCompany', [
            'company' => $this->reportCompany($companyId),
            'from' => $from,
            'to' => $to,
            'kooperantId' => $kooperantId ? (int) $kooperantId : null,
            'kooperant' => $kooperant,
            'sections' => $sections,
            'grandTotal' => $grandTotal,
        ]);
    }

    // ─── Filters / shared lookups ──────────────────────────────────────────────

    private function periodParams(Request $request): array
    {
        $year = session('current_year', date('Y'));

        return [
            $request->query('from', "{$year}-01-01"),
            $request->query('to', "{$year}-12-31"),
        ];
    }

    private function reportCompany(int $companyId): ?array
    {
        $company = Company::where('id', $companyId)
            ->first(['id', 'name', 'tax_id', 'embs', 'address', 'bank_name', 'bank_account', 'logo_path']);

        return $company?->toArray();
    }

    // ─── Aggregated balance queries (Бруто биланс family) ──────────────────────

    private function accountBalances(int $companyId, string $from, string $to): Collection
    {
        return DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('chart_of_accounts as coa', 'coa.code', '=', 'jel.account_code')
            ->where('je.company_id', $companyId)
            ->where('je.status', JournalEntryStatus::Posted->value)
            ->whereNotNull('jel.account_code')
            ->selectRaw(
                'coa.code as account_code, coa.name as account_name, coa.class as class, coa.parent_code as parent_code,'.
                ' SUM(CASE WHEN COALESCE(jel.line_date, je.entry_date) < ? THEN jel.debit ELSE 0 END) as open_debit,'.
                ' SUM(CASE WHEN COALESCE(jel.line_date, je.entry_date) < ? THEN jel.credit ELSE 0 END) as open_credit,'.
                ' SUM(CASE WHEN COALESCE(jel.line_date, je.entry_date) BETWEEN ? AND ? THEN jel.debit ELSE 0 END) as period_debit,'.
                ' SUM(CASE WHEN COALESCE(jel.line_date, je.entry_date) BETWEEN ? AND ? THEN jel.credit ELSE 0 END) as period_credit',
                [$from, $from, $from, $to, $from, $to]
            )
            ->groupBy('coa.code', 'coa.name', 'coa.class', 'coa.parent_code')
            ->orderBy('coa.code')
            ->get();
    }

    private function accountCompanyBalances(int $companyId, string $from, string $to): Collection
    {
        return DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('chart_of_accounts as coa', 'coa.code', '=', 'jel.account_code')
            ->leftJoin('kooperanti as k', 'k.id', '=', 'jel.kooperant_id')
            ->where('je.company_id', $companyId)
            ->where('je.status', JournalEntryStatus::Posted->value)
            ->whereNotNull('jel.account_code')
            ->selectRaw(
                'coa.code as account_code, coa.name as account_name, coa.class as class,'.
                ' jel.kooperant_id as kooperant_id, k.name as kooperant_name,'.
                ' SUM(CASE WHEN COALESCE(jel.line_date, je.entry_date) < ? THEN jel.debit ELSE 0 END) as open_debit,'.
                ' SUM(CASE WHEN COALESCE(jel.line_date, je.entry_date) < ? THEN jel.credit ELSE 0 END) as open_credit,'.
                ' SUM(CASE WHEN COALESCE(jel.line_date, je.entry_date) BETWEEN ? AND ? THEN jel.debit ELSE 0 END) as period_debit,'.
                ' SUM(CASE WHEN COALESCE(jel.line_date, je.entry_date) BETWEEN ? AND ? THEN jel.credit ELSE 0 END) as period_credit',
                [$from, $from, $from, $to, $from, $to]
            )
            ->groupBy('coa.code', 'coa.name', 'coa.class', 'jel.kooperant_id', 'k.name')
            ->orderBy('coa.code')
            ->orderBy('k.name')
            ->get();
    }

    private function hasActivity(object $row): bool
    {
        return (float) $row->open_debit !== 0.0
            || (float) $row->open_credit !== 0.0
            || (float) $row->period_debit !== 0.0
            || (float) $row->period_credit !== 0.0;
    }

    private function shapeBucket(array $row): array
    {
        $openDebit = (float) $row['open_debit'];
        $openCredit = (float) $row['open_credit'];
        $periodDebit = (float) $row['period_debit'];
        $periodCredit = (float) $row['period_credit'];
        $closeDebit = $openDebit + $periodDebit;
        $closeCredit = $openCredit + $periodCredit;

        return [
            'opening' => ['debit' => $openDebit, 'credit' => $openCredit, 'balance' => $openDebit - $openCredit],
            'period' => ['debit' => $periodDebit, 'credit' => $periodCredit, 'balance' => $periodDebit - $periodCredit],
            'closing' => ['debit' => $closeDebit, 'credit' => $closeCredit, 'balance' => $closeDebit - $closeCredit],
        ];
    }

    private function sumBuckets(iterable $items): array
    {
        $sum = [
            'opening' => ['debit' => 0.0, 'credit' => 0.0],
            'period' => ['debit' => 0.0, 'credit' => 0.0],
            'closing' => ['debit' => 0.0, 'credit' => 0.0],
        ];

        foreach ($items as $item) {
            foreach (['opening', 'period', 'closing'] as $bucket) {
                $sum[$bucket]['debit'] += $item[$bucket]['debit'];
                $sum[$bucket]['credit'] += $item[$bucket]['credit'];
            }
        }

        foreach (['opening', 'period', 'closing'] as $bucket) {
            $sum[$bucket]['balance'] = $sum[$bucket]['debit'] - $sum[$bucket]['credit'];
        }

        return $sum;
    }

    private function groupByClass(Collection $accounts): Collection
    {
        return $accounts->groupBy('class')->map(fn ($items, $class) => [
            'class' => (int) $class,
            'accounts' => $items->values(),
            'total' => $this->sumBuckets($items),
        ])->sortKeys()->values();
    }

    // ─── Ledger card queries (Аналитичка картица family) ───────────────────────

    private function ledgerOpening(int $companyId, string $from, \Closure $scope): array
    {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('je.company_id', $companyId)
            ->where('je.status', JournalEntryStatus::Posted->value)
            ->whereRaw('COALESCE(jel.line_date, je.entry_date) < ?', [$from]);
        $scope($query);

        $row = $query->selectRaw('COALESCE(SUM(jel.debit),0) as d, COALESCE(SUM(jel.credit),0) as c')->first();

        return ['debit' => (float) $row->d, 'credit' => (float) $row->c];
    }

    private function ledgerLines(int $companyId, string $from, string $to, \Closure $scope): Collection
    {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->leftJoin('kooperanti as k', 'k.id', '=', 'jel.kooperant_id')
            ->where('je.company_id', $companyId)
            ->where('je.status', JournalEntryStatus::Posted->value)
            ->whereRaw('COALESCE(jel.line_date, je.entry_date) BETWEEN ? AND ?', [$from, $to]);
        $scope($query);

        return $query->selectRaw(
            'jel.id, jel.account_code, jel.kooperant_id, k.name as kooperant_name,'.
            ' jel.sort_order, jel.debit, jel.credit, jel.description, jel.closing_reference,'.
            ' je.id as journal_entry_id, je.group_code, je.sequence_number,'.
            ' COALESCE(jel.line_date, je.entry_date) as effective_date'
        )
            ->orderBy('effective_date')
            ->orderBy('je.id')
            ->orderBy('jel.sort_order')
            ->get();
    }

    private function buildLedgerRows(iterable $lines, array $opening): array
    {
        $balance = $opening['debit'] - $opening['credit'];
        $rows = [];

        foreach ($lines as $line) {
            $balance += (float) $line->debit - (float) $line->credit;
            $rows[] = [
                'date' => $line->effective_date,
                'voucher' => $this->voucherNumberFor($line->group_code, $line->sequence_number),
                'account_code' => $line->account_code,
                'kooperant_id' => $line->kooperant_id,
                'kooperant_name' => $line->kooperant_name,
                'description' => $line->description,
                'closing_reference' => $line->closing_reference,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => $balance,
            ];
        }

        return $rows;
    }

    private function rowTotals(array $rows): array
    {
        return [
            'debit' => array_sum(array_column($rows, 'debit')),
            'credit' => array_sum(array_column($rows, 'credit')),
        ];
    }

    private function voucherNumberFor($groupCode, $sequenceNumber): ?string
    {
        if ($groupCode === null || $sequenceNumber === null) {
            return null;
        }

        return $groupCode.'-'.str_pad((string) $sequenceNumber, 4, '0', STR_PAD_LEFT);
    }
}
