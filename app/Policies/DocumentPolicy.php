<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant', 'company_admin']);
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->hasAnyRole(['admin', 'accountant'])) {
            return true;
        }

        return $user->hasRole('company_admin')
            && $document->company->users()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant', 'company_admin']);
    }

    public function verify(User $user, Document $document): bool
    {
        return $user->hasAnyRole(['admin', 'accountant'])
            && $document->status === DocumentStatus::AiProcessed;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasRole('admin');
    }
}
