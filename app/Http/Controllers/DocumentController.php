<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\IntakeChannel;
use App\Http\Requests\StoreDocumentRequest;
use App\Jobs\ProcessDocumentJob;
use App\Models\Company;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Document::class);

        $user  = $request->user();
        $query = Document::with(['company:id,name', 'uploader:id,name'])->latest();

        if ($user->hasRole('company_admin')) {
            $query->whereIn('company_id', $user->companies()->pluck('companies.id'));
        }

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $companies = $user->hasRole('company_admin')
            ? $user->companies()->orderBy('name')->get(['companies.id', 'companies.name'])
            : Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('documents/Index', [
            'documents' => $query->paginate(20)->withQueryString(),
            'companies' => $companies,
            'filters'   => $request->only(['company_id', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Document::class);

        $user      = $request->user();
        $companies = $user->hasRole('company_admin')
            ? $user->companies()->orderBy('name')->get(['companies.id', 'companies.name'])
            : Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('documents/Create', [
            'companies'         => $companies,
            'selectedCompanyId' => $request->integer('company_id') ?: null,
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $companyId = $request->company_id;

        if ($request->user()->hasRole('company_admin')) {
            abort_unless(
                $request->user()->companies()->where('companies.id', $companyId)->exists(),
                403,
            );
        }

        $file = $request->file('file');

        $googlePath = $file->store("documents/{$companyId}", 'google');
        $localPath  = $file->store("temp/documents", 'local');

        $document = Document::create([
            'company_id'     => $companyId,
            'uploaded_by'    => $request->user()->id,
            'type'           => $request->type,
            'status'         => DocumentStatus::Pending,
            'intake_channel' => IntakeChannel::Portal,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path'   => $googlePath,
            'mime_type'      => $file->getMimeType(),
            'file_size'      => $file->getSize(),
        ]);

        ProcessDocumentJob::dispatch($document, $localPath);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Документот е прикачен и се праќа на AI обработка.']);

        return to_route('documents.show', $document);
    }

    public function show(Document $document): Response
    {
        $this->authorize('view', $document);

        return Inertia::render('documents/Show', [
            'document' => $document->load(['company:id,name', 'uploader:id,name', 'extraction', 'lineItems.suggestedAccount']),
        ]);
    }

    public function verify(Document $document, Request $request): RedirectResponse
    {
        $this->authorize('verify', $document);

        $document->update([
            'status'      => \App\Enums\DocumentStatus::Verified,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $document->extraction?->update(['is_confirmed' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Документот е верификуван.']);

        return to_route('documents.show', $document);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        Storage::disk('google')->delete($document->storage_path);
        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Документот е избришан.']);

        return to_route('documents.index');
    }
}
