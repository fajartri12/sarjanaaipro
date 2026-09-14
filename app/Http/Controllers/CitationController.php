<?php

namespace App\Http\Controllers;

use App\Models\Citation;
use App\Models\Project;
use App\Models\Reference;
use App\Models\ThesisSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitationController extends Controller
{
    public function references(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $refs = Reference::ownedBy($request->user()->id)
            ->where(function ($q) use ($project) {
                $q->where('project_id', $project->id)
                    ->orWhereNull('project_id');
            })
            ->orderBy('authors')
            ->get(['id', 'authors', 'year', 'title', 'container']);

        return response()->json($refs);
    }

    public function store(Request $request, Project $project, ThesisSection $section): JsonResponse
    {
        $this->authorize('update', $project);
        abort_unless($section->project_id === $project->id, 404);

        $data = $request->validate([
            'reference_id' => ['required', 'exists:references,id'],
            'style' => ['required', 'in:apa,ieee,harvard'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $ref = Reference::ownedBy($request->user()->id)
            ->whereKey($data['reference_id'])
            ->firstOrFail();

        // Cegah sitasi duplikat ke referensi yang sama di section yang sama
        $exists = Citation::where('thesis_section_id', $section->id)
            ->where('reference_id', $ref->id)
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Referensi ini sudah disitasi di bagian ini.'], 422);
        }

        $number = $section->citations()->count() + 1;
        $inText = $ref->inTextCitation($data['style'], $number);

        $citation = Citation::create([
            'project_id' => $project->id,
            'reference_id' => $ref->id,
            'thesis_section_id' => $section->id,
            'style' => $data['style'],
            'in_text' => $inText,
            'position' => $data['position'] ?? $number,
        ]);

        return response()->json([
            'id' => $citation->id,
            'in_text' => $inText,
            'reference' => [
                'id' => $ref->id,
                'authors' => $ref->authors,
                'year' => $ref->year,
                'title' => $ref->title,
            ],
        ]);
    }
}