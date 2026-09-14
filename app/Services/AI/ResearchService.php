<?php

namespace App\Services\AI;

use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use App\Support\AiText;

class ResearchService
{
    public function __construct(
        private AiService $ai,
        private VectorSearch $vector,
    ) {}

    /** Analisis satu dokumen dan simpan metadata hasilnya. */
    public function analyzeDocument(Document $document): Document
    {
        if (! $document->extracted_text) {
            abort(422, 'Teks dokumen belum tersedia. Tunggu proses ekstraksi selesai.');
        }

        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($document->project?->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::analyzeDocument($document->extracted_text, $document->project?->degree_level)],
            ],
            feature: 'research',
            user: $document->user,
            project: $document->project,
            options: ['json' => true],
        );

        $data = $result->json();

        $document->update([
            'title' => AiText::plain($data['title'] ?? $document->title),
            'author' => AiText::plain($data['author'] ?? $document->author),
            'year' => $data['year'] ?? $document->year,
            'metadata' => array_merge($document->metadata ?? [], [
                'purpose' => AiText::plain($data['purpose'] ?? null),
                'method' => AiText::plain($data['method'] ?? null),
                'dataset' => AiText::plain($data['dataset'] ?? null),
                'result' => AiText::plain($data['result'] ?? null),
                'limitations' => AiText::plain($data['limitations'] ?? null),
                'research_gap' => AiText::plain($data['research_gap'] ?? null),
                'relevance' => AiText::plain($data['relevance'] ?? null),
            ]),
        ]);

        return $document;
    }

    /**
     * Temukan research gap dari beberapa dokumen sekaligus.
     *
     * @param  array<int, int>  $documentIds
     */
    public function findGap(User $user, string $topic, array $documentIds, ?Project $project = null): array
    {
        $documents = Document::whereIn('id', $documentIds)
            ->where('user_id', $user->id)
            ->get()
            ->map(fn ($d) => [
                'title' => $d->title,
                'year' => $d->year,
                'abstract' => $d->metadata['purpose'] ?? $d->extracted_text,
            ]);

        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($project?->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::researchGap($topic, $documents->all(), $project?->degree_level)],
            ],
            feature: 'research',
            user: $user,
            project: $project,
            options: ['json' => true],
        );

        $data = $result->json();

        return [
            'gaps' => AiText::cleanArray($data['gaps'] ?? []),
            'opportunities' => AiText::cleanArray($data['opportunities'] ?? []),
            'summary' => AiText::plain($data['summary'] ?? ''),
        ];
    }

    /**
     * Cari kutipan dokumen yang relevan dengan teks tertentu, tanpa memanggil AI.
     *
     * Dipakai editor draft untuk menampilkan sumber yang nyambung dengan
     * paragraf yang sedang ditulis. Murah karena cuma vektor/kata kunci.
     *
     * @return array<int, array{content: string, label: string, score: float}>
     */
    public function relevantSources(User $user, string $query, ?Project $project = null, int $topK = 5): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 15) {
            return [];
        }

        $documents = Document::with('chunks')
            ->when($project, fn ($q) => $q->where('project_id', $project->id))
            ->where('user_id', $user->id)
            ->where('status', 'ready')
            ->get();

        if ($documents->isEmpty()) {
            return [];
        }

        return $this->vector->search($documents, $query, $topK);
    }

    /**
     * Tanya jawab berbasis dokumen (RAG).
     *
     * @param  array<int, int>  $documentIds
     * @return array{answer: string, sources: array<int, array{label: string, content: string, score: float}>}
     */
    public function ask(User $user, string $question, array $documentIds = [], ?Project $project = null): array
    {
        $documents = Document::with('chunks')
            ->when($documentIds, fn ($q) => $q->whereIn('id', $documentIds))
            ->when(! $documentIds && $project, fn ($q) => $q->where('project_id', $project->id))
            ->where('user_id', $user->id)
            ->where('status', 'ready')
            ->get();

        $sources = $this->vector->search($documents, $question);

        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($project?->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::chat($question, $sources, $project?->degree_level)],
            ],
            feature: 'research',
            user: $user,
            project: $project,
        );

        return [
            'answer' => AiText::plain($result->text),
            'sources' => $sources,
        ];
    }

    /**
     * Asisten metodologi penelitian — menjawab pertanyaan tentang metode.
     *
     * Tidak bergantung pada dokumen; memakai pengetahuan umum + konteks project
     * (metode yang dipilih, jenjang) untuk memberi arahan yang relevan.
     */
    public function methodology(User $user, string $question, ?Project $project = null): string
    {
        $result = $this->ai->chat(
            messages: [
                ['role' => 'system', 'content' => PromptLibrary::base($project?->degree_level)],
                ['role' => 'user', 'content' => PromptLibrary::methodologyAssistant(
                    $question,
                    $project?->method,
                    $project?->degree_level,
                )],
            ],
            feature: 'research',
            user: $user,
            project: $project,
        );

        return AiText::plain($result->text);
    }
}
