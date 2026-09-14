<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\UniversityTemplate;
use App\Services\AI\UsageLimiter;
use App\Support\Labels;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function __construct(private UsageLimiter $limiter) {}

    public function index(Request $request): \Illuminate\View\View
    {
        $user = $request->user();

        return view('projects.index', [
            'projects' => $user->projects()
                ->withCount(['sections', 'references'])
                ->latest()
                ->get(),
            'activeCount' => $user->projects()->byStatus('aktif')->count(),
            'projectLimit' => $this->limiter->limit($user, 'projects'),
        ]);
    }

    public function create(Request $request): \Illuminate\View\View
    {
        return view('projects.create', [
            'projectLimit' => $this->limiter->limit($request->user(), 'projects'),
            'activeCount' => $request->user()->projects()->byStatus('aktif')->count(),
            'templates' => $this->templates($request->user()),
        ]);
    }

    public function edit(Request $request, Project $project): \Illuminate\View\View
    {
        $this->authorize('update', $project);

        return view('projects.edit', [
            'project' => $project->only([
                'id', 'name', 'title', 'study_program', 'university', 'template_id',
                'advisor', 'research_type', 'method', 'degree_level', 'deadline', 'description',
            ]),
            'templates' => $this->templates($request->user()),
        ]);
    }

    /**
     * Template yang boleh dipakai user. Template premium disembunyikan kalau
     * tidak ada langganan aktif — bukan dikunci di UI saja, tapi tidak ditawarkan.
     */
    private function templates($user): \Illuminate\Support\Collection
    {
        $query = UniversityTemplate::active();

        if (config('template.premium_requires_subscription') && ! $user->activeSubscription) {
            $query->where('is_premium', false);
        }

        return $query->get();
    }

    /** Template yang boleh dipilih user, dipakai untuk validasi simpan. */
    private function allowedTemplateIds($user): array
    {
        return $this->templates($user)->pluck('id')->all();
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'study_program' => ['nullable', 'string', 'max:255'],
            'university' => ['nullable', 'string', 'max:255'],
            'advisor' => ['nullable', 'string', 'max:255'],
            'research_type' => ['nullable', 'string', 'max:100'],
            'method' => ['nullable', 'string', 'max:100'],
            'degree_level' => ['nullable', Rule::in(array_keys(Labels::DEGREE_LEVEL))],
            'deadline' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'template_id' => ['nullable', Rule::in($this->allowedTemplateIds($request->user()))],
        ]);

        $data['degree_level'] = ($data['degree_level'] ?? null) ?: Labels::DEGREE_LEVEL_DEFAULT;

        $activeCount = $request->user()->projects()->byStatus('aktif')->count();
        $limit = $this->limiter->limit($request->user(), 'projects');

        if ($limit !== null && $activeCount >= $limit) {
            return back()->withErrors(['limit' => 'Batas jumlah project untuk plan Anda sudah tercapai.']);
        }

        $project = $request->user()->projects()->create($data);
        $project->seedSections();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project '.$project->documentType().' berhasil dibuat.');
    }

    public function show(Project $project): \Illuminate\View\View
    {
        $this->authorize('view', $project);
        $project->load([
            'sections', 'titles' => fn ($q) => $q->orderByDesc('is_selected')->orderByDesc('updated_at'),
            'references', 'documents' => fn ($q) => $q->latest(),
            'semproSessions' => fn ($q) => $q->latest()->take(5),
        ]);

        return view('projects.show', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'study_program' => ['nullable', 'string', 'max:255'],
            'university' => ['nullable', 'string', 'max:255'],
            'advisor' => ['nullable', 'string', 'max:255'],
            'research_type' => ['nullable', 'string', 'max:100'],
            'method' => ['nullable', 'string', 'max:100'],
            'degree_level' => ['nullable', Rule::in(array_keys(Labels::DEGREE_LEVEL))],
            'deadline' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'template_id' => ['nullable', Rule::in($this->allowedTemplateIds($request->user()))],
        ]);

        $project->update($data);

        return back()->with('success', 'Project berhasil diperbarui.');
    }

    public function destroy(Project $project): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project dihapus.');
    }
}