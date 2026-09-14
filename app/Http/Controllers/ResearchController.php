<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\Project;
use App\Services\AI\ResearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResearchController extends Controller
{
    public function __construct(private ResearchService $research) {}

    public function index(Request $request): \Illuminate\View\View
    {
        $user = $request->user();

        return view('research.index', [
            'documents' => Document::ownedBy($user->id)
                ->with('project:id,name')
                ->latest()
                ->get(),
            'projects' => $user->projects()->get(['id', 'name', 'title', 'method']),
        ]);
    }

    public function upload(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ]);

        $project = null;

        if (! empty($data['project_id'])) {
            $project = Project::findOrFail($data['project_id']);
            abort_unless($project->user_id === $request->user()->id, 403);
        }

        // dokumen mahasiswa selalu private, disimpan di disk local (bukan public)
        $file = $request->file('file');
        $path = $file->store("documents/{$request->user()->id}", 'local');

        $document = Document::create([
            'user_id' => $request->user()->id,
            'project_id' => $project?->id,
            'title' => $data['title'] ?? null,
            'author' => $data['author'] ?? null,
            'year' => $data['year'] ?? null,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'status' => 'pending',
        ]);

        ProcessDocument::dispatch($document);

        return back()->with('success', 'Dokumen diunggah. Ekstraksi teks sedang diproses di latar belakang.');
    }

    public function show(Request $request, Document $document): \Illuminate\View\View
    {
        $this->authorize('view', $document);

        return view('research.show', [
            'document' => $document->load('chunks:id,document_id,position,tokens'),
        ]);
    }

    /** Unduh lewat signed URL — file tidak bisa diakses langsung dari luar. */
    public function download(Request $request, Document $document)
    {
        $this->authorize('view', $document);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function analyze(Request $request, Document $document): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('view', $document);

        $this->research->analyzeDocument($document);

        return back()->with('success', 'Analisis jurnal selesai.');
    }

    public function ask(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['integer', 'exists:documents,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $ownedDocumentIds = $request->user()->documents()
            ->whereIn('id', $data['document_ids'] ?? [])
            ->pluck('id')->all();
        abort_unless(count($ownedDocumentIds) === count($data['document_ids'] ?? []), 403);

        $project = ! empty($data['project_id']) ? Project::findOrFail($data['project_id']) : null;

        if ($project) {
            abort_unless($project->user_id === $request->user()->id, 403);
        }

        $result = $this->research->ask(
            $request->user(),
            $data['question'],
            $data['document_ids'] ?? [],
            $project,
        );

        return back()->with('researchAnswer', $result);
    }

    public function gap(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'exists:documents,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $ownedDocumentIds = $request->user()->documents()
            ->whereIn('id', $data['document_ids'])
            ->pluck('id')->all();
        abort_unless(count($ownedDocumentIds) === count($data['document_ids']), 403);

        $project = ! empty($data['project_id']) ? Project::findOrFail($data['project_id']) : null;

        $result = $this->research->findGap(
            $request->user(),
            $data['topic'],
            $data['document_ids'],
            $project,
        );

        return back()->with('researchGap', $result);
    }

    public function destroy(Document $document): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $document);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('success', 'Dokumen dihapus.');
    }

    /**
     * Asisten metodologi penelitian — jawab pertanyaan tentang metode.
     * Endpoint JSON, dipanggil dari panel di halaman riset.
     */
    public function methodology(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'min:10', 'max:2000'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $project = ! empty($data['project_id']) ? Project::findOrFail($data['project_id']) : null;

        if ($project) {
            abort_unless($project->user_id === $request->user()->id, 403);
        }

        $answer = $this->research->methodology(
            $request->user(),
            $data['question'],
            $project,
        );

        return response()->json(['answer' => $answer]);
    }
}
