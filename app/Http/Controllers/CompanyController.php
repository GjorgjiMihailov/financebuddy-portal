<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::with('creator')
            ->latest()
            ->paginate(20);

        return Inertia::render('companies/Index', [
            'companies' => $companies,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Company::class);

        return Inertia::render('companies/Create');
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = Company::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Компанијата е успешно додадена.']);

        return to_route('companies.show', $company);
    }

    public function show(Company $company): Response
    {
        $this->authorize('view', $company);

        return Inertia::render('companies/Show', [
            'company' => $company->load('creator'),
        ]);
    }

    public function edit(Company $company): Response
    {
        $this->authorize('update', $company);

        return Inertia::render('companies/Edit', [
            'company' => $company,
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Промените се зачувани.']);

        return to_route('companies.show', $company);
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Компанијата е избришана.']);

        return to_route('companies.index');
    }
}
