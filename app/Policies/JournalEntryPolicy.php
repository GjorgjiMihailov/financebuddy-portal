<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function view(User $user, JournalEntry $entry): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function post(User $user, JournalEntry $entry): bool
    {
        return $user->hasAnyRole(['admin', 'accountant'])
            && $entry->status->value === 'draft';
    }
}
