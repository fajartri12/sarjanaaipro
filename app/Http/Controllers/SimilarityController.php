<?php

namespace App\Http\Controllers;

use App\Models\PlagiarismReport;
use App\Models\Project;
use App\Services\AI\SimilarityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimilarityController extends Controller
{
    public function __construct(private SimilarityService $similarity) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('similarity.index', [
            'projects' => $user->projects()
                ->withCount('sections')
                ->latest()
                ->get(['id', 'name', 'title', 'progress', 'status']),
            'reports' => PlagiarismReport::ownedBy($user->id)
                ->with(['project:id,name', 'section:id,title,chapter'])
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function show(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        return view('similarity.show', [
            'project' => $project->load(['sections' => fn ($q) => $q->whereNotNull('content')]),
            'latestBySection' => PlagiarismReport::latestPerSection($project->id),
            'latestFull' => PlagiarismReport::latestFull($project->id),
        ]);
    }

    /** Cek kemiripan satu section. */
    public function checkSection(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'section_id' => ['required', 'exists:thesis_sections,id'],
        ]);

        $section = $project->sections()->findOrFail($data['section_id']);

        $result = $this->similarity->checkSection($project, $section);

        $report = PlagiarismReport::create([
            'user_id' => $request->user()->id,
            'project_id' => $project->id,
            'thesis_section_id' => $section->id,
            'type' => 'section',
            'similarity_score' => $result['score'],
            'matches' => $result['matches'],
            'summary' => $result['summary'],
        ]);

        return back()
            ->with('success', 'Pemeriksaan kemiripan selesai.')
            ->with('report', $report);
    }

    /** Cek kemiripan seluruh project. */
    public function checkFullProject(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $result = $this->similarity->checkFullProject($project);

        $report = PlagiarismReport::create([
            'user_id' => $request->user()->id,
            'project_id' => $project->id,
            'type' => 'full_project',
            'similarity_score' => $result['score'],
            'matches' => $result['sections'],
            'summary' => $result['summary'],
        ]);

        return back()
            ->with('success', 'Pemeriksaan kemiripan seluruh project selesai.')
            ->with('report', $report);
    }
}
