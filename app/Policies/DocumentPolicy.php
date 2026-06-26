<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasAnyRole(['admin', 'accountant'])
            || $document->company->users()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasRole('admin');
    }
}
