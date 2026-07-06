<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Kooperant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KooperantController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Kooperant::with('company:id,name')
            ->where('company_id', $this->currentCompanyId($request))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('edb', 'like', "%{$s}%");
            }))
            ->orderBy('name');

        $kooperanti = $query->paginate(20)->withQueryString();

        return Inertia::render('kooperanti/Index', [
            'kooperanti' => $kooperanti,
            'filters'     => $request->only(['type', 'search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'  => ['required', 'exists:companies,id'],
            'name'        => ['required', 'string', 'max:255'],
            'edb'         => ['nullable', 'string', 'size:13', 'regex:/^\d{13}$/'],
            'embs'        => ['nullable', 'string', 'size:7', 'regex:/^\d{7}$/'],
            'address'     => ['nullable', 'string', 'max:500'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'email'       => ['nullable', 'email', 'max:255'],
            'is_vat_payer'=> ['boolean'],
            'type'        => ['required', 'in:client,supplier,both'],
            'is_active'   => ['boolean'],
        ]);

        Kooperant::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Кооперантот е успешно додаден.']);

        return back();
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $companyId = $this->currentCompanyId($request);

        $validated = $request->validate([
            'rows'                => ['required', 'array', 'min:1'],
            'rows.*.name'         => ['required', 'string', 'max:255'],
            'rows.*.edb'          => ['nullable', 'string', 'size:13', 'regex:/^\d{13}$/'],
            'rows.*.embs'         => ['nullable', 'string', 'size:7', 'regex:/^\d{7}$/'],
            'rows.*.address'      => ['nullable', 'string', 'max:500'],
            'rows.*.phone'        => ['nullable', 'string', 'max:50'],
            'rows.*.email'        => ['nullable', 'email', 'max:255'],
            'rows.*.is_vat_payer' => ['boolean'],
            'rows.*.type'         => ['nullable', 'in:client,supplier,both'],
        ]);

        $count = DB::transaction(function () use ($validated, $companyId) {
            foreach ($validated['rows'] as $row) {
                Kooperant::create([
                    'company_id'   => $companyId,
                    'name'         => $row['name'],
                    'edb'          => $row['edb'] ?? null,
                    'embs'         => $row['embs'] ?? null,
                    'address'      => $row['address'] ?? null,
                    'phone'        => $row['phone'] ?? null,
                    'email'        => $row['email'] ?? null,
                    'is_vat_payer' => $row['is_vat_payer'] ?? false,
                    'type'         => $row['type'] ?? 'both',
                    'is_active'    => true,
                ]);
            }

            return count($validated['rows']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Додадени {$count} кооперанти."]);

        return back();
    }

    public function update(Request $request, Kooperant $kooperant): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'edb'         => ['nullable', 'string', 'size:13', 'regex:/^\d{13}$/'],
            'embs'        => ['nullable', 'string', 'size:7', 'regex:/^\d{7}$/'],
            'address'     => ['nullable', 'string', 'max:500'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'email'       => ['nullable', 'email', 'max:255'],
            'is_vat_payer'=> ['boolean'],
            'type'        => ['required', 'in:client,supplier,both'],
            'is_active'   => ['boolean'],
        ]);

        $kooperant->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Кооперантот е ажуриран.']);

        return back();
    }

    public function destroy(Kooperant $kooperant): RedirectResponse
    {
        $kooperant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Кооперантот е избришан.']);

        return back();
    }

    public function searchApi(Request $request): JsonResponse
    {
        $companyId = $this->currentCompanyId($request);
        $q         = (string) $request->query('q', '');

        $kooperanti = Kooperant::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('edb', 'like', "{$q}%")
                      ->orWhere('embs', 'like', "{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'edb', 'embs', 'address', 'phone', 'email']);

        return response()->json($kooperanti);
    }

    public function forCompany(Request $request, Company $company): JsonResponse
    {
        $type = $request->query('type');

        $kooperanti = Kooperant::where('company_id', $company->id)
            ->where('is_active', true)
            ->when($type, fn ($q, $t) => $q->whereIn('type', [$t, 'both']))
            ->orderBy('name')
            ->get(['id', 'name', 'edb', 'is_vat_payer', 'type']);

        return response()->json($kooperanti);
    }
}
