<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class YearContextController extends Controller
{
    public function select(): Response
    {
        $currentYear = (int) date('Y');
        $years       = range($currentYear + 1, $currentYear - 4);

        return Inertia::render('SelectYear', [
            'years'       => $years,
            'currentYear' => $currentYear,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        session(['current_year' => (int) $validated['year']]);

        return redirect()->intended(route('dashboard'));
    }

    public function clear(): RedirectResponse
    {
        session()->forget('current_year');

        return to_route('year.select');
    }
}
