<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ThesisTitle;
use App\Services\AI\TitleService;
use Illuminate\Http\Request;

class TitleController extends Controller
{
    public function __construct(private TitleService $titles) {}

    public function index(Request $request): \Illuminate\View\View
    {
        $user = $request->user();

        return view('titles.index', [
            'titles' => $user->titles()
                ->with('project:id,name')
                ->latest()
                ->get(),
            'projects' => $user->projects()->get(['id', 'name', 'title', 'method']),
        ]);
    }

    public function generate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'study_program' => ['nullable', 'string', 'max:255'],
            'topic' => ['required', 'string', 'max:255'],
            'object' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:100'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $project = $data['project_id'] ?? null ? Project::findOrFail($data['project_id']) : null;

        if ($project && $project->user_id !== $request->user()->id) {
            abort(403, 'Project bukan milik Anda.');
        }

        $this->titles->generate($request->user(), $data, $project);

        return back()->with('success', 'Alternatif judul berhasil dibuat.');
    }

    public function show(ThesisTitle $title): \Illuminate\View\View
    {
        abort_unless($title->user_id === request()->user()->id, 403);

        return view('titles.show', [
            'title' => $title->load('project:id,name'),
        ]);
    }

    public function analyze(Request $request, ThesisTitle $title): \Illuminate\Http\RedirectResponse
    {
        abort_unless($title->user_id === $request->user()->id, 403);

        $this->titles->validate($title, $request->string('context')->toString() ?: null);

        return back()->with('success', 'Analisis judul selesai.');
    }

    public function select(Request $request, ThesisTitle $title): \Illuminate\Http\RedirectResponse
    {
        abort_unless($title->user_id === $request->user()->id, 403);

        $project = Project::findOrFail($request->integer('project_id'));

        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Project bukan milik Anda.');
        }

        $this->titles->select($title, $project);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Judul dijadikan judul project.');
    }
}