<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\ThesisTitle;
use App\Models\User;
use App\Support\AiText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TitleService
{
    public function __construct(private AiService $ai) {}

    /**
     * Hasilkan alternatif judul lalu simpan sebagai record ThesisTitle.
     *
     * @return Collection<int, ThesisTitle>
     */
    public function generate(User $user, array $input, ?Project $project = null): Collection
    {
        $level = $project?->degree_level ?? $user->degree_level;

        $result = $this->ai->chatWithSystem(
            system: PromptLibrary::base($level),
            userPrompt: PromptLibrary::titles($input, $level),
            feature: 'title',
            user: $user,
            project: $project,
            options: ['json' => true, 'temperature' => 0.8],
        );

        $items = $result->json();

        // Model kadang membungkus array dalam {"titles": [...]}
        if (isset($items['titles']) && is_array($items['titles'])) {
            $items = $items['titles'];
        }

        return DB::transaction(fn () => collect($items)
            ->filter(fn ($item) => is_array($item) && ! empty($item['title']))
            ->map(fn ($item) => ThesisTitle::create([
                'user_id' => $user->id,
                'project_id' => $project?->id,
                'title' => AiText::plain($item['title']),
                'description' => AiText::plain($item['description'] ?? null),
                'study_program' => $input['study_program'] ?? null,
                'topic' => $input['topic'] ?? null,
                'object' => $input['object'] ?? null,
                'location' => $input['location'] ?? null,
                'method' => $input['method'] ?? null,
                'keywords' => $input['keywords'] ?? null,
                'relevance' => $this->score($item['relevance'] ?? null),
                'novelty' => $this->score($item['novelty'] ?? null),
                'feasibility' => $this->score($item['feasibility'] ?? null),
                'complexity' => $this->score($item['complexity'] ?? null),
                'gap_score' => $this->score($item['gap_score'] ?? null),
                'research_gap' => AiText::plain($item['research_gap'] ?? null),
                'variables' => isset($item['variables']) && is_array($item['variables'])
                    ? AiText::cleanArray($item['variables'])
                    : null,
                'recommendation' => AiText::plain($item['recommendation'] ?? null),
            ])));
    }

    /** Analisis mendalam satu judul dan simpan hasilnya. */
    public function validate(ThesisTitle $title, ?string $context = null): ThesisTitle
    {
        $level = $title->project?->degree_level ?? $title->user->degree_level;

        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($level)],
                ['role' => 'user', 'content' => PromptLibrary::validateTitle($title->title, $context, $level)],
            ],
            feature: 'title',
            user: $title->user,
            project: $title->project,
            options: ['json' => true],
        );

        $data = $result->json();

        $title->update([
            'relevance' => $this->score($data['relevance'] ?? $title->relevance),
            'novelty' => $this->score($data['novelty'] ?? $title->novelty),
            'feasibility' => $this->score($data['feasibility'] ?? $title->feasibility),
            'complexity' => $this->score($data['complexity'] ?? $title->complexity),
            'gap_score' => $this->score($data['gap_score'] ?? $title->gap_score),
            'research_gap' => AiText::plain($data['research_gap'] ?? $title->research_gap),
            'variables' => isset($data['variables']) && is_array($data['variables'])
                ? AiText::cleanArray($data['variables'])
                : $title->variables,
            'recommendation' => AiText::plain($data['recommendation'] ?? $title->recommendation),
            'risk' => AiText::plain($data['risk'] ?? $title->risk),
        ]);

        return $title;
    }

    /** Jadikan judul ini judul resmi project. */
    public function select(ThesisTitle $title, Project $project): Project
    {
        ThesisTitle::where('project_id', $project->id)->update(['is_selected' => false]);

        $title->update([
            'project_id' => $project->id,
            'is_selected' => true,
        ]);

        $project->update(['title' => $title->title]);

        return $project;
    }

    private function score(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) max(0, min(100, (int) $value));
    }
}
