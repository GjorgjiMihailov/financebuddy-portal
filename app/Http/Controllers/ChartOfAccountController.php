<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');

        $accounts = ChartOfAccount::where('is_active', true)
            ->where('allows_posting', true)
            ->where(function ($query) use ($q) {
                $query->where('code', 'like', "{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
            })
            ->orderBy('code')
            ->limit(20)
            ->get(['code', 'name', 'class', 'account_type']);

        return response()->json($accounts);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        $query = ChartOfAccount::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%");
            }))
            ->when($request->class !== null && $request->class !== '', fn ($q) => $q->where('class', $request->class))
            ->orderBy('code');

        $accounts = $query->paginate(60)->withQueryString();

        return Inertia::render('settings/accounts/Index', [
            'accounts' => $accounts,
            'filters'  => $request->only(['search', 'class']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ChartOfAccount::class);

        $validated = $request->validate([
            'code'           => ['required', 'string', 'max:10', 'unique:chart_of_accounts,code'],
            'name'           => ['required', 'string', 'max:255'],
            'class'          => ['required', 'integer', 'between:0,9'],
            'account_type'   => ['required', 'string', 'in:asset,liability,equity,revenue,expense,cost_center,bank_only'],
            'parent_code'    => ['nullable', 'string', 'exists:chart_of_accounts,code'],
            'allows_posting' => ['boolean'],
            'is_active'      => ['boolean'],
        ]);

        ChartOfAccount::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Контото е успешно додадено.']);

        return to_route('settings.accounts.index');
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $this->authorize('create', ChartOfAccount::class);

        $validated = $request->validate([
            'rows'               => ['required', 'array', 'min:1'],
            'rows.*.parent_code' => ['required', 'string', 'exists:chart_of_accounts,code'],
            'rows.*.code'        => ['required', 'string', 'max:10', 'distinct', 'unique:chart_of_accounts,code'],
            'rows.*.name'        => ['required', 'string', 'max:255'],
        ]);

        $count = DB::transaction(function () use ($validated) {
            foreach ($validated['rows'] as $row) {
                $parent = ChartOfAccount::findOrFail($row['parent_code']);

                ChartOfAccount::create([
                    'code'           => $row['code'],
                    'name'           => $row['name'],
                    'class'          => $parent->class,
                    'account_type'   => $parent->account_type,
                    'parent_code'    => $parent->code,
                    'allows_posting' => true,
                    'is_active'      => true,
                ]);
            }

            return count($validated['rows']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Додадени {$count} аналитички конта."]);

        return back();
    }

    public function update(Request $request, ChartOfAccount $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'allows_posting' => ['boolean'],
            'is_active'      => ['boolean'],
        ]);

        $account->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Контото е успешно ажурирано.']);

        return to_route('settings.accounts.index');
    }
}
