<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
