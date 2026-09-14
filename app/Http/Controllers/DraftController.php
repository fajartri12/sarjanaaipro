<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ThesisSection;
use App\Models\ThesisSectionVersion;
use App\Services\AI\DraftService;
use App\Services\AI\ResearchService;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    public function __construct(
        private DraftService $drafts,
        private ResearchService $research,
    ) {}

    public function index(Project $project): \Illuminate\View\View
    {
        $this->authorize('view', $project);

        $sections = $project->sections()->get();
        $chapters = $sections->groupBy('chapter')->map(fn ($items) => [
            'chapter' => $items->first()->chapter,
            'sections' => $items->values(),
            'wordCount' => $items->sum('word_count'),
            'done' => $items->where('status', 'done')->count(),
            'total' => $items->count(),
        ])->values();

        return view('draft.index', [
            'project' => $project->only(['id', 'name', 'title', 'method']),
            'chapters' => $chapters,
            'wordCount' => $sections->sum('word_count'),
            'canExportDocx' => auth()->user()->currentPlan()?->limit('export_docx') !== 0,
        ]);
    }

    public function show(Project $project, ThesisSection $section): \Illuminate\View\View
    {
        $this->authorize('view', $project);
        abort_unless($section->project_id === $project->id, 404);

        return view('draft.show', [
            'project' => $project->only(['id', 'name', 'title', 'method']),
            'section' => $section,
            'sections' => $project->sections()->get(['id', 'key', 'chapter', 'title', 'status', 'word_count']),
            'references' => $project->references()->orderBy('authors')->get(['id', 'authors', 'year', 'title', 'container']),
        ]);
    }

    public function save(Request $request, Project $project, ThesisSection $section): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $project);
        abort_unless($section->project_id === $project->id, 404);

        $data = $request->validate([
            'content' => ['nullable', 'string'],
        ]);

        $this->drafts->save($section, $data['content'] ?? '', $request->user());

        return back()->with('success', 'Bagian tersimpan.');
    }

    /**
     * Autosave dari editor. Dipanggil berkala oleh JS dan mengembalikan JSON,
     * jadi tab tetap terbuka dan tidak ada reload.
     */
    public function autosave(Request $request, Project $project, ThesisSection $section): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $project);
        abort_unless($section->project_id === $project->id, 404);

        $data = $request->validate([
            'content' => ['nullable', 'string'],
        ]);

        $this->drafts->save($section, $data['content'] ?? '', $request->user(), 'autosave');

        return response()->json([
            'saved_at' => now()->toIso8601String(),
            'word_count' => $section->word_count,
            'status' => $section->status,
        ]);
    }

    /** Riwayat versi satu bagian — endpoint JSON untuk panel di editor. */
    public function versions(Request $request, Project $project, ThesisSection $section): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($section->project_id === $project->id, 404);

        $versions = $section->versions()
            ->limit(25)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'word_count' => $v->word_count,
                'reason' => $v->reason,
                'at' => $v->created_at->diffForHumans(),
                'at_full' => $v->created_at->translatedFormat('j M Y, H:i'),
            ]);

        return response()->json(['versions' => $versions]);
    }

    /** Kembalikan bagian ke versi lama. */
    public function restore(Request $request, Project $project, ThesisSection $section, ThesisSectionVersion $version): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $project);
        abort_unless($section->project_id === $project->id, 404);

        $this->drafts->restore($section, $version, $request->user());

        return back()->with('success', 'Versi dikembalikan.');
    }

    public function generate(Project $project, ThesisSection $section): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $project);
        abort_unless($section->project_id === $project->id, 404);

        $this->drafts->generate($project, $section);

        return back()->with('success', "Draft {$section->title} berhasil dibuat.");
    }

    public function action(Request $request, Project $project, ThesisSection $section): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $project);
        abort_unless($section->project_id === $project->id, 404);

        $data = $request->validate([
            'action' => ['required', 'string'],
        ]);

        $result = $this->drafts->applyAction($project, $section, $data['action']);

        return back()
            ->with('success', "Aksi {$data['action']} selesai.")
            ->with('explanation', $result['explanation']);
    }

    /**
     * Cari kutipan dokumen yang relevan dengan isi bagian ini.
     * Endpoint JSON untuk panel "Sumber terkait" di editor.
     */
    public function sources(Request $request, Project $project, ThesisSection $section): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($section->project_id === $project->id, 404);

        $data = $request->validate([
            'query' => ['required', 'string', 'min:15'],
        ]);

        $sources = $this->research->relevantSources(
            $request->user(),
            $data['query'],
            $project,
        );

        return response()->json([
            'sources' => array_map(fn ($s) => [
                'label' => $s['label'] ?? '',
                'content' => mb_substr($s['content'] ?? '', 0, 400),
                'score' => round((float) ($s['score'] ?? 0), 3),
            ], $sources),
        ]);
    }
}