<?php

namespace App\Services;

use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;

class MonthlyJournalResolver
{
    private const MONTH_NAMES = [
        'Јануари', 'Февруари', 'Март', 'Април', 'Мај', 'Јуни',
        'Јули', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември',
    ];

    /**
     * Find or create the single consolidated journal entry for a given
     * company + group + calendar month (sequence_number = month number).
     */
    public static function resolve(int $companyId, int $groupCode, string $date, string $descriptionPrefix, int $userId): JournalEntry
    {
        $year  = (int) date('Y', strtotime($date));
        $month = (int) date('n', strtotime($date));

        $entry = JournalEntry::where('company_id', $companyId)
            ->where('group_code', $groupCode)
            ->where('year', $year)
            ->where('sequence_number', $month)
            ->lockForUpdate()
            ->first();

        if ($entry) {
            return $entry;
        }

        $monthName = self::MONTH_NAMES[$month - 1];

        return JournalEntry::create([
            'company_id'      => $companyId,
            'group_code'      => $groupCode,
            'year'            => $year,
            'sequence_number' => $month,
            'entry_date'      => $date,
            'description'     => "{$descriptionPrefix} – {$monthName} {$year}",
            'status'          => JournalEntryStatus::Draft,
            'created_by'      => $userId,
        ]);
    }

    /**
     * Find or create an entry for an explicit "group-sequence" voucher number
     * (e.g. "30-0007"), used when the user overrides the month bucket manually.
     */
    public static function resolveExplicit(int $companyId, int $groupCode, int $sequenceNumber, string $date, string $descriptionPrefix, int $userId): JournalEntry
    {
        $year = (int) date('Y', strtotime($date));

        $entry = JournalEntry::where('company_id', $companyId)
            ->where('group_code', $groupCode)
            ->where('year', $year)
            ->where('sequence_number', $sequenceNumber)
            ->lockForUpdate()
            ->first();

        if ($entry) {
            return $entry;
        }

        $monthName = self::MONTH_NAMES[((int) date('n', strtotime($date))) - 1];

        return JournalEntry::create([
            'company_id'      => $companyId,
            'group_code'      => $groupCode,
            'year'            => $year,
            'sequence_number' => $sequenceNumber,
            'entry_date'      => $date,
            'description'     => "{$descriptionPrefix} – {$monthName} {$year}",
            'status'          => JournalEntryStatus::Draft,
            'created_by'      => $userId,
        ]);
    }
}
