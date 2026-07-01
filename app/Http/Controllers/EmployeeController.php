<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Employee::with('company:id,name')
            ->when($request->company_id, fn ($q, $id) => $q->where('company_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('embg', 'like', "%{$s}%");
            }))
            ->orderBy('last_name')
            ->orderBy('first_name');

        $employees = $query->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('employees/Index', [
            'employees' => $employees,
            'companies' => $companies,
            'filters'   => $request->only(['company_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('employees/Create', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'   => ['required', 'exists:companies,id'],
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'embg'         => ['nullable', 'string', 'size:13'],
            'position'     => ['nullable', 'string', 'max:255'],
            'net_salary'   => ['nullable', 'numeric', 'min:0'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'is_active'    => ['boolean'],
            'hired_at'     => ['nullable', 'date'],
        ]);

        Employee::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Вработениот е успешно додаден.']);

        return to_route('employees.index');
    }

    public function edit(Employee $employee): Response
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('employees/Edit', [
            'employee'  => $employee,
            'companies' => $companies,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'embg'         => ['nullable', 'string', 'size:13'],
            'position'     => ['nullable', 'string', 'max:255'],
            'net_salary'   => ['nullable', 'numeric', 'min:0'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'is_active'    => ['boolean'],
            'hired_at'     => ['nullable', 'date'],
        ]);

        $employee->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Вработениот е ажуриран.']);

        return to_route('employees.index');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Вработениот е избришан.']);

        return to_route('employees.index');
    }
}
