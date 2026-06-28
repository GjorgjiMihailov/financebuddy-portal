<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\IntakeChannel;
use App\Http\Requests\StoreDocumentRequest;
use App\Jobs\ProcessDocumentJob;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentUploadedNotification;
use App\Notifications\DocumentVerifiedNotification;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $googlePath  = $file->store("documents/{$companyId}", 'google') ?: '';
        $localPath   = $file->store("temp/documents", 'local');
        $driveFileId = $googlePath ? $this->resolveFileIdBySearch($googlePath) : null;

        $document = Document::create([
            'company_id'        => $companyId,
            'uploaded_by'       => $request->user()->id,
            'type'              => $request->type,
            'status'            => DocumentStatus::Pending,
            'intake_channel'    => IntakeChannel::Portal,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path'      => $googlePath,
            'drive_file_id'     => $driveFileId,
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
        ]);

        ProcessDocumentJob::dispatch($document, $localPath);

        $document->load(['company', 'uploader']);
        $recipients = User::role(['admin', 'accountant'])->get();
        Notification::send($recipients, new DocumentUploadedNotification($document));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Документот е прикачен и се праќа на AI обработка.']);

        return to_route('documents.show', $document);
    }

    public function show(Request $request, Document $document): Response
    {
        $this->authorize('view', $document);

        $document->load(['company:id,name', 'uploader:id,name', 'extraction', 'lineItems.suggestedAccount']);

        $journalEntry = $document->journalEntries()->first(['id', 'status']);

        return Inertia::render('documents/Show', [
            'document'     => $document,
            'journalEntry' => $journalEntry,
            'canBook'      => $request->user()->can('create', \App\Models\JournalEntry::class),
        ]);
    }

    public function file(Request $request, Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        $fileId = $document->drive_file_id
            ?? $this->resolveFileIdByPath($document->storage_path)
            ?? $this->resolveFileIdBySearch($document->storage_path);

        if ($fileId && !$document->drive_file_id) {
            $document->updateQuietly(['drive_file_id' => $fileId]);
        }

        abort_unless($fileId, 404, 'Фајлот не е достапен за преглед.');

        $service      = app(GoogleDrive::class);
        $httpClient   = $service->getClient()->authorize();
        $driveUrl     = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";
        $httpResponse = $httpClient->request('GET', $driveUrl, ['stream' => true]);
        $body         = $httpResponse->getBody();

        return response()->stream(
            function () use ($body) {
                while (!$body->eof()) {
                    echo $body->read(8192);
                    flush();
                }
            },
            200,
            [
                'Content-Type'        => $document->mime_type,
                'Content-Disposition' => 'inline; filename="' . rawurlencode($document->original_filename) . '"',
                'Cache-Control'       => 'private, max-age=3600',
            ]
        );
    }

    private function resolveFileIdByPath(string $path): ?string
    {
        try {
            $adapter = Storage::disk('google')->getAdapter();
            if (method_exists($adapter, 'getFileId')) {
                return $adapter->getFileId($path);
            }
        } catch (\Throwable) {}
        return null;
    }

    private function resolveFileIdBySearch(string $storedPath): ?string
    {
        try {
            $service  = app(GoogleDrive::class);
            $filename = basename($storedPath);
            $safe     = str_replace("'", "\\'", $filename);
            $fileList = $service->files->listFiles([
                'q'                         => "name = '{$safe}' and trashed = false",
                'fields'                    => 'files(id)',
                'pageSize'                  => 1,
                'orderBy'                   => 'createdTime desc',
                'includeItemsFromAllDrives' => true,
                'supportsAllDrives'         => true,
            ])->getFiles();
            return !empty($fileList) ? $fileList[0]->getId() : null;
        } catch (\Throwable) {
            return null;
        }
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

        $document->load(['company.users', 'verifier']);
        $recipients = $document->company->users->filter(fn ($u) => $u->hasRole('company_admin'));
        Notification::send($recipients, new DocumentVerifiedNotification($document));

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
