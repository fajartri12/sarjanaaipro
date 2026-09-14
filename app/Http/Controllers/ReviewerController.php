<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AI\ReviewerService;
use Illuminate\Http\Request;

class ReviewerController extends Controller
{
    public function __construct(private ReviewerService $reviewer) {}

    public function index(Request $request): \Illuminate\View\View
    {
        $user = $request->user();

        return view('reviewer.index', [
            'projects' => $user->projects()
                ->withCount('sections')
                ->latest()
                ->get(['id', 'name', 'title', 'progress', 'status', 'method']),
        ]);
    }

    public function show(Request $request, Project $project): \Illuminate\View\View
    {
        $this->authorize('view', $project);

        return view('reviewer.show', [
            'project' => $project->load(['sections', 'reviews' => fn ($q) => $q->latest()]),
        ]);
    }

    public function review(Request $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $project);

        $result = $this->reviewer->review($project);

        $review = $project->reviews()->create([
            'user_id' => $request->user()->id,
            'score' => $this->average($result['scores']),
            'scores' => $result['scores'],
            'summary' => $result['summary'],
            'recommendations' => $result['recommendations'],
        ]);

        return back()
            ->with('success', 'Review draft selesai.')
            ->with('review', $review);
    }

    /** Review satu bagian saja — lebih cepat dan hemat token. */
    public function reviewSection(Request $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'section_id' => ['required', 'exists:thesis_sections,id'],
        ]);

        $section = $project->sections()->findOrFail($data['section_id']);

        if (! $section->content) {
            return back()->withErrors(['section_id' => 'Bagian ini masih kosong.']);
        }

        $result = $this->reviewer->reviewSection($project, $section);

        $project->reviews()->create([
            'user_id' => $request->user()->id,
            'section_id' => $section->id,
            'score' => $this->average($result['scores']),
            'scores' => $result['scores'],
            'summary' => $result['summary'],
            'recommendations' => $result['recommendations'],
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Review {$section->title} selesai.");
    }

    /**
     * Review satu paragraf yang dipilih user di editor.
     * Endpoint JSON — dipanggil via fetch dari JavaScript.
     */
    public function reviewParagraph(Request $request, Project $project): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'paragraph' => ['required', 'string', 'min:10'],
        ]);

        $result = $this->reviewer->reviewParagraph($project, $data['paragraph']);

        return response()->json($result);
    }

    /**
     * Periksa konsistensi judul, rumusan masalah, tujuan, dan metode.
     * Endpoint JSON — dipanggil via fetch dari halaman reviewer.
     */
    public function consistency(Request $request, Project $project): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $project);

        $result = $this->reviewer->checkConsistency($project);

        return response()->json($result);
    }

    private function average(array $scores): int
    {
        return $scores === [] ? 0 : (int) round(array_sum($scores) / count($scores));
    }
}
