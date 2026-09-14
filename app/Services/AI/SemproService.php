<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\SemproAnswer;
use App\Models\SemproEvaluation;
use App\Models\SemproQuestion;
use App\Models\SemproSession;
use App\Support\AiText;
use Illuminate\Support\Facades\DB;

class SemproService
{
    public function __construct(private AiService $ai) {}

    public function startSession(Project $project): SemproSession
    {
        return $project->semproSessions()->create([
            'user_id' => $project->user_id,
            'status' => 'active',
        ]);
    }

    /** @return array<int, SemproQuestion> */
    public function generateQuestions(SemproSession $session, int $count = 5, ?string $focusCategory = null): array
    {
        $project = $session->project;
        $draft = $project->sections()->where('content', '!=', null)->get()
            ->map(fn ($s) => strip_tags((string) $s->content))
            ->implode("\n\n");

        // Pertanyaan yang sudah ada, supaya AI tidak mengulang.
        $asked = $session->questions()->pluck('question')->all();

        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($project->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::semproQuestions([
                    'title' => $project->title ?? $project->name,
                    'method' => $project->method,
                    'degree_level' => $project->degree_level,
                    'draft' => $draft,
                    'focus_category' => $focusCategory,
                    'asked' => $asked,
                ], $count)],
            ],
            feature: 'sempro',
            user: $session->user,
            project: $project,
            options: ['json' => true],
        );

        $items = $result->json();

        return DB::transaction(function () use ($items, $project, $session) {
            $created = [];
            $position = $session->questions()->max('position') ?? -1;

            foreach ($items as $item) {
                if (empty($item['question'])) {
                    continue;
                }

                $created[] = SemproQuestion::create([
                    'sempro_session_id' => $session->id,
                    'project_id' => $project->id,
                    'category' => $item['category'] ?? 'umum',
                    'difficulty' => in_array($item['difficulty'] ?? null, ['dasar', 'menengah', 'sulit'], true)
                        ? $item['difficulty']
                        : null,
                    'question' => AiText::plain($item['question']),
                    'expected_points' => AiText::plain($item['expected_points'] ?? null),
                    'position' => ++$position,
                    'source' => 'ai',
                ]);
            }

            $session->update(['question_count' => $session->questions()->count()]);

            return $created;
        });
    }

    /** Simpan jawaban mahasiswa lalu nilai langsung. */
    public function submitAnswer(SemproSession $session, SemproQuestion $question, string $answer): SemproEvaluation
    {
        $record = SemproAnswer::updateOrCreate(
            [
                'sempro_session_id' => $session->id,
                'sempro_question_id' => $question->id,
            ],
            ['answer' => $answer],
        );

        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($session->project->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::evaluateAnswer(
                    $question->question,
                    $answer,
                    $question->expected_points,
                    $session->project->degree_level,
                )],
            ],
            feature: 'sempro',
            user: $session->user,
            project: $session->project,
            options: ['json' => true],
        );

        $data = $result->json();

        return SemproEvaluation::updateOrCreate(
            [
                'sempro_session_id' => $session->id,
                'sempro_question_id' => $question->id,
            ],
            [
                'sempro_answer_id' => $record->id,
                'concept' => $this->score($data['concept'] ?? null),
                'relevance' => $this->score($data['relevance'] ?? null),
                'argumentation' => $this->score($data['argumentation'] ?? null),
                'methodology' => $this->score($data['methodology'] ?? null),
                'clarity' => $this->score($data['clarity'] ?? null),
                'confidence' => $this->score($data['confidence'] ?? null),
                'score' => $this->score($data['score'] ?? null),
                'feedback' => AiText::plain($data['feedback'] ?? null),
            ],
        );
    }

    public function finish(SemproSession $session): SemproSession
    {
        $evaluations = $session->evaluations()->whereNotNull('score')->get();

        if ($evaluations->isEmpty()) {
            abort(422, 'Belum ada jawaban yang dinilai.');
        }

        $session->update([
            'status' => 'finished',
            'score' => (int) round($evaluations->avg('score')),
            'finished_at' => now(),
        ]);

        return $session;
    }

    private function score(mixed $value): ?int
    {
        return $value === null ? null : (int) max(0, min(100, (int) $value));
    }
}
