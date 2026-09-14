<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Project;
use App\Models\Reference;
use App\Services\CrossRef;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    public function __construct(private CrossRef $crossref) {}

    public function index(Request $request): \Illuminate\View\View
    {
        $user = $request->user();

        $references = Reference::ownedBy($user->id)
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->search($request->q)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('references.index', [
            'references' => $references,
            'projects' => $user->projects()->get(['id', 'name', 'title']),
            'filters' => $request->only('q', 'type', 'project_id'),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;

        $reference = Reference::create($data);

        return back()->with('success', 'Referensi disimpan.')
            ->with('reference', $reference);
    }

    public function update(Request $request, Reference $reference): \Illuminate\Http\RedirectResponse
    {
        abort_unless($reference->user_id === $request->user()->id, 403);

        $reference->update($this->validated($request));

        return back()->with('success', 'Referensi diperbarui.');
    }

    public function destroy(Request $request, Reference $reference): \Illuminate\Http\RedirectResponse
    {
        abort_unless($reference->user_id === $request->user()->id, 403);

        $reference->delete();

        return back()->with('success', 'Referensi dihapus.');
    }

    /**
     * Halaman "Cari Jurnal": mencari metadata publikasi di CrossRef.
     * Hasilnya belum tersimpan — user memilih mana yang mau dijadikan referensi.
     */
    public function search(Request $request): \Illuminate\View\View
    {
        $user = $request->user();
        $query = trim((string) $request->query('q', ''));

        $results = $query !== ''
            ? $this->crossref->search($query, [
                'year_from' => $request->integer('year_from') ?: null,
                'year_to' => $request->integer('year_to') ?: null,
            ])
            : [];

        return view('references.search', [
            'results' => $results,
            'query' => $query,
            'projects' => $user->projects()->get(['id', 'name', 'title']),
            'filters' => $request->only('q', 'year_from', 'year_to', 'project_id'),
            // DOI yang sudah dimiliki user, supaya tombol Simpan bisa ditandai.
            'savedDois' => Reference::ownedBy($user->id)
                ->withDoi()
                ->pluck('doi')
                ->flip(),
        ]);
    }

    /**
     * Impor metadata dari DOI (mis. 10.1016/j.jbusres.2020.01.001).
     * Dipakai tombol "Ambil dari DOI" dan tombol "Simpan" di halaman Cari Jurnal.
     */
    public function importDoi(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'doi' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $project = $this->ownedProject($request, $data['project_id'] ?? null);
        $doi = $this->normalizeDoi($data['doi']);

        $already = Reference::ownedBy($request->user()->id)
            ->where('doi', $doi)
            ->exists();

        if ($already) {
            return back()->with('success', 'Jurnal ini sudah ada di daftar referensi.');
        }

        $meta = $this->crossref->find($doi);

        if (! $meta) {
            return back()->withErrors(['doi' => 'Metadata DOI tidak ditemukan. Isi manual saja.']);
        }

        Reference::create([
            'user_id' => $request->user()->id,
            'project_id' => $project?->id,
            ...$meta,
        ]);

        return back()->with('success', 'Referensi berhasil diimpor dari DOI.');
    }

    /** Buang awalan URL doi.org supaya DOI bisa langsung dipakai di API. */
    private function normalizeDoi(string $doi): string
    {
        return preg_replace('#^https?://(dx\.)?doi\.org/#', '', trim($doi));
    }

    /** Buat daftar pustaka + sitasi dalam teks untuk referensi terpilih. */
    public function bibliography(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'style' => ['required', 'in:apa,ieee,harvard'],
            'reference_ids' => ['required', 'array', 'min:1'],
            'reference_ids.*' => ['integer', 'exists:references,id'],
        ]);

        $references = Reference::ownedBy($request->user()->id)
            ->whereIn('id', $data['reference_ids'])
            ->orderBy('authors')
            ->get();

        $entries = $references->values()->map(fn ($ref, $i) => [
            'id' => $ref->id,
            'bibliography' => $ref->bibliography($data['style'], $i + 1),
            'in_text' => $ref->inTextCitation($data['style'], $i + 1),
        ]);

        return back()->with('bibliography', [
            'style' => $data['style'],
            'entries' => $entries,
            'plain' => $entries->pluck('bibliography')->implode("\n\n"),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'document_id' => ['nullable', 'exists:documents,id'],
            'type' => ['required', 'in:journal,book,chapter,thesis,conference,web,other'],
            'authors' => ['nullable', 'string', 'max:500'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'title' => ['required', 'string', 'max:500'],
            'container' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable', 'string', 'max:50'],
            'issue' => ['nullable', 'string', 'max:50'],
            'pages' => ['nullable', 'string', 'max:50'],
            'doi' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        if (! empty($data['project_id'])) {
            $owns = Project::ownedBy($request->user()->id)
                ->whereKey($data['project_id'])
                ->exists();

            abort_unless($owns, 403);
        }

        if (! empty($data['document_id'])) {
            $ownsDoc = Document::ownedBy($request->user()->id)
                ->whereKey($data['document_id'])
                ->exists();

            abort_unless($ownsDoc, 403);
        }

        return $data;
    }

    /** Project milik user, atau null. Mencegah referensi ditempel ke project orang lain. */
    private function ownedProject(Request $request, $projectId): ?Project
    {
        if (! $projectId) {
            return null;
        }

        $project = Project::find($projectId);

        abort_unless($project && $project->user_id === $request->user()->id, 403);

        return $project;
    }
}
