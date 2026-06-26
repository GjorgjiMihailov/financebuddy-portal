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

        $query = Document::with(['company:id,name', 'uploader:id,name'])
            ->latest();

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return Inertia::render('documents/Index', [
            'documents' => $query->paginate(20)->withQueryString(),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'filters'   => $request->only(['company_id', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Document::class);

        return Inertia::render('documents/Create', [
            'companies'         => Company::orderBy('name')->get(['id', 'name']),
            'selectedCompanyId' => $request->integer('company_id') ?: null,
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $file      = $request->file('file');
        $companyId = $request->company_id;
        $path      = $file->store("documents/{$companyId}", 'google');

        $document = Document::create([
            'company_id'     => $companyId,
            'uploaded_by'    => $request->user()->id,
            'type'           => $request->type,
            'status'         => DocumentStatus::Pending,
            'intake_channel' => IntakeChannel::Portal,
            'filename'       => $file->getClientOriginalName(),
            'storage_path'   => $path,
            'mime_type'      => $file->getMimeType(),
            'file_size'      => $file->getSize(),
        ]);

        ProcessDocumentJob::dispatch($document);

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

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        Storage::disk('google')->delete($document->storage_path);
        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Документот е избришан.']);

        return to_route('documents.index');
    }
}
