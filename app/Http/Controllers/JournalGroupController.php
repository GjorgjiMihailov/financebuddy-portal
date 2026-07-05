<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalGroupController extends Controller
{
    public function index(): Response
    {
        $groups = JournalGroup::orderBy('code')->get();

        return Inertia::render('settings/journal-groups/Index', [
            'groups' => $groups,
        ]);
    }

    public function list(): JsonResponse
    {
        $groups = JournalGroup::orderBy('code')->get(['code', 'name']);
        return response()->json($groups);
    }

    public function nextSequence(Request $request): JsonResponse
    {
        $groupCode = (int) $request->query('group_code');
        $year      = (int) $request->query('year');
        $companyId = (int) $request->query('company_id');
        $date      = $request->query('date');

        if (in_array($groupCode, [20, 30], true) && $date) {
            // Влезни/Излезни фактури — консолидирано по месец: покажи го постојниот
            // налог за тој месец (ако веќе постои) наместо секогаш нов број.
            $seq    = (int) date('n', strtotime($date));
            $exists = JournalEntry::where('group_code', $groupCode)
                ->where('year', $year)
                ->where('company_id', $companyId)
                ->where('sequence_number', $seq)
                ->exists();

            $voucherNumber = $groupCode . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            return response()->json(['next_sequence' => $seq, 'voucher_number' => $voucherNumber, 'exists' => $exists]);
        }

        $seq = (JournalEntry::where('group_code', $groupCode)
            ->where('year', $year)
            ->where('company_id', $companyId)
            ->max('sequence_number') ?? 0) + 1;

        $voucherNumber = $groupCode . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        return response()->json(['next_sequence' => $seq, 'voucher_number' => $voucherNumber, 'exists' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', JournalGroup::class);

        $validated = $request->validate([
            'code' => ['required', 'integer', 'min:0', 'max:99', 'unique:journal_groups,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        JournalGroup::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Групата е додадена.']);

        return to_route('settings.journal-groups.index');
    }

    public function destroy(JournalGroup $journalGroup): RedirectResponse
    {
        $this->authorize('viewAny', JournalGroup::class);

        if ($journalGroup->journalEntries()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Не може да се избрише група со постоечки налози.']);
            return back();
        }

        $journalGroup->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Групата е избришана.']);

        return to_route('settings.journal-groups.index');
    }
}