<?php

namespace App\Policies;

use App\Models\JournalGroup;
use App\Models\User;

class JournalGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }
}