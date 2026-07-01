<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Kontragent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KontragentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Kontragent::with('company:id,name')
            ->when(
                $user->hasRole('company_admin'),
                fn ($q) => $q->whereHas('company.users', fn ($u) => $u->where('users.id', $user->id))
            )
            ->when($request->company_id, fn ($q, $id) => $q->where('company_id', $id))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('edb', 'like', "%{$s}%");
            }))
            ->orderBy('name');

        $kontragenti = $query->paginate(20)->withQueryString();
        $companies   = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('kontragenti/Index', [
            'kontragenti' => $kontragenti,
            'companies'   => $companies,
            'filters'     => $request->only(['company_id', 'type', 'search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'  => ['required', 'exists:companies,id'],
            'name'        => ['required', 'string', 'max:255'],
            'edb'         => ['required', 'string', 'size:13', 'regex:/^\d{13}$/'],
            'embs'        => ['nullable', 'string', 'size:7', 'regex:/^\d{7}$/'],
            'address'     => ['nullable', 'string', 'max:500'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'email'       => ['nullable', 'email', 'max:255'],
            'is_vat_payer'=> ['boolean'],
            'type'        => ['required', 'in:client,supplier,both'],
            'is_active'   => ['boolean'],
        ]);

        Kontragent::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Контрагентот е успешно додаден.']);

        return back();
    }

    public function update(Request $request, Kontragent $kontragent): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'edb'         => ['required', 'string', 'size:13', 'regex:/^\d{13}$/'],
            'embs'        => ['nullable', 'string', 'size:7', 'regex:/^\d{7}$/'],
            'address'     => ['nullable', 'string', 'max:500'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'email'       => ['nullable', 'email', 'max:255'],
            'is_vat_payer'=> ['boolean'],
            'type'        => ['required', 'in:client,supplier,both'],
            'is_active'   => ['boolean'],
        ]);

        $kontragent->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Контрагентот е ажуриран.']);

        return back();
    }

    public function destroy(Kontragent $kontragent): RedirectResponse
    {
        $kontragent->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Контрагентот е избришан.']);

        return back();
    }

    public function forCompany(Request $request, Company $company): JsonResponse
    {
        $type = $request->query('type');

        $kontragenti = Kontragent::where('company_id', $company->id)
            ->where('is_active', true)
            ->when($type, fn ($q, $t) => $q->whereIn('type', [$t, 'both']))
            ->orderBy('name')
            ->get(['id', 'name', 'edb', 'is_vat_payer', 'type']);

        return response()->json($kontragenti);
    }
}
