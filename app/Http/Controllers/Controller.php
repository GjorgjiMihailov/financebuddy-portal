<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    protected function currentCompanyId(Request $request): int
    {
        if ($request->user()->hasRole('company_admin')) {
            return (int) $request->user()->companies()->value('companies.id');
        }

        return (int) session('current_company_id');
    }
}
