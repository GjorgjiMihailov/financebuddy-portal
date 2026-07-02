<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyContextController extends Controller
{
    public function select(): Response
    {
        $companies = Company::withCount([
            'documents as pending_count' => fn ($q) => $q->whereIn('status', [
                DocumentStatus::Pending,
                DocumentStatus::AiProcessing,
                DocumentStatus::AiProcessed,
            ]),
        ])->orderBy('name')->get();

        return Inertia::render('SelectCompany', ['companies' => $companies]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['company_id' => ['required', 'exists:companies,id']]);
        session(['current_company_id' => (int) $validated['company_id']]);

        return redirect()->intended(route('dashboard'));
    }

    public function clear(): RedirectResponse
    {
        session()->forget('current_company_id');

        return to_route('company.select');
    }
}
