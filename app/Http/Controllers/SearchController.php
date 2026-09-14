<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Project;
use App\Models\Reference;
use App\Models\ThesisSection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return view('search', [
                'q' => $q,
                'results' => collect(),
            ]);
        }

        $user = $request->user();

        $projects = Project::ownedBy($user->id)
            ->search($q)
            ->withCount('sections')
            ->get()
            ->map(fn ($p) => [
                'type' => 'project',
                'url' => route('projects.show', $p),
                'label' => $p->name,
                'sub' => $p->title,
                'meta' => $p->sections_count . ' bagian',
            ]);

        $sections = ThesisSection::ownedBy($user->id)
            ->search($q)
            ->with('project:id,name')
            ->get()
            ->map(fn ($s) => [
                'type' => 'section',
                'url' => route('draft.show', [$s->project_id, $s]),
                'label' => $s->chapter . ' · ' . $s->title,
                'sub' => 'Project: ' . ($s->project->name ?? '—'),
                'meta' => $s->word_count . ' kata',
            ]);

        $documents = Document::inUserProjects($user->id)
            ->search($q)
            ->get()
            ->map(fn ($d) => [
                'type' => 'document',
                'url' => route('research.show', $d),
                'label' => $d->title,
                'sub' => $d->author ? 'Oleh: ' . $d->author : '',
                'meta' => $d->year ?? '',
            ]);

        $references = Reference::inUserProjects($user->id)
            ->search($q)
            ->get()
            ->map(fn ($r) => [
                'type' => 'reference',
                'url' => route('references.index'),
                'label' => $r->title,
                'sub' => $r->authors ? 'Oleh: ' . $r->authors : '',
                'meta' => $r->year ?? '',
            ]);

        $results = $projects
            ->concat($sections)
            ->concat($documents)
            ->concat($references)
            ->sortByDesc(fn ($r) => $r['label'])
            ->values();

        return view('search', [
            'q' => $q,
            'results' => $results,
        ]);
    }
}