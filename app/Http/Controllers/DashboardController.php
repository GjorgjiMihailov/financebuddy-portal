<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user           = $request->user();
        $isCompanyAdmin = $user->hasRole('company_admin');
        $isAdmin        = $user->hasRole('admin');

        $base = Document::query();

        if ($isCompanyAdmin) {
            $base->whereIn('company_id', $user->companies()->pluck('companies.id'));
        }

        $stats = [
            'awaiting_verification' => (clone $base)->where('status', DocumentStatus::AiProcessed)->count(),
            'verified'              => (clone $base)->where('status', DocumentStatus::Verified)->count(),
            'processing'            => (clone $base)->whereIn('status', [DocumentStatus::Pending, DocumentStatus::AiProcessing])->count(),
            'total_documents'       => (clone $base)->count(),
            'total_companies'       => $isCompanyAdmin ? null : Company::count(),
            'total_users'           => $isAdmin ? User::count() : null,
        ];

        $recentDocuments = (clone $base)
            ->with(['company:id,name', 'uploader:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Document $doc) => [
                'id'                => $doc->id,
                'original_filename' => $doc->original_filename,
                'type'              => $doc->type,
                'status'            => $doc->status,
                'company'           => $doc->company,
                'uploader'          => $doc->uploader,
                'created_at'        => $doc->created_at,
            ]);

        return Inertia::render('Dashboard', [
            'stats'           => $stats,
            'recentDocuments' => $recentDocuments,
        ]);
    }
}
