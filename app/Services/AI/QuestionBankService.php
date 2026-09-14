<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\QuestionBank;
use App\Models\SemproEvaluation;
use App\Models\SemproQuestion;

/**
 * Question bank & simulasi adaptif sempro.
 *
 * Dua sumber pertanyaan:
 * 1. Bank statis (`question_bank`) — pertanyaan baku per kategori/metode/tingkat.
 *    Gratis dan instan, dipakai lebih dulu supaya kuota AI tidak habis.
 * 2. AI (`SemproService::generateQuestions`) — melengkapi dengan pertanyaan
 *    spesifik project. Hanya dipanggil kalau bank belum cukup.
 */
class QuestionBankService
{
    /**
     * Ambil pertanyaan dari bank sesuai konteks project.
     *
     * @return \Illuminate\Support\Collection<int, QuestionBank>
     */
    public function pick(Project $project, ?string $category = null, ?string $difficulty = null, int $limit = 5)
    {
        return QuestionBank::query()
            ->where('is_active', true)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($difficulty, fn ($q) => $q->where('difficulty', $difficulty))
            // Pertanyaan cocok metode didahulukan; pertanyaan generik (method null) tetap masuk.
            ->where(fn ($q) => $q->whereNull('method')
                ->orWhere('method', $this->normalizeMethod($project->method)))
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Tentukan kategori yang perlu dilatih lagi berdasarkan hasil evaluasi.
     *
     * @return array<int, string> kategori terlemah, urut dari paling lemah
     */
    public function weakCategories(Project $project, int $threshold = 70, int $take = 2): array
    {
        $rows = SemproEvaluation::query()
            ->whereHas('session', fn ($q) => $q->where('project_id', $project->id))
            ->whereNotNull('score')
            ->with('question:id,category')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows
            ->groupBy(fn ($e) => $e->question?->category ?? 'umum')
            ->map(fn ($group) => (int) round($group->avg('score')))
            ->filter(fn ($avg) => $avg < $threshold)
            ->sort()
            ->take($take)
            ->keys()
            ->all();
    }

    /**
     * Skor kesiapan sempro: rata-rata skor + cakupan kategori + konsistensi latihan.
     *
     * @return array{score:int, label:string, tone:string, aspects:array<string,int>, weak:array<int,string>, advice:string}
     */
    public function readiness(Project $project): array
    {
        $evaluations = SemproEvaluation::query()
            ->whereHas('session', fn ($q) => $q->where('project_id', $project->id))
            ->whereNotNull('score')
            ->with('question:id,category')
            ->get();

        if ($evaluations->isEmpty()) {
            return [
                'score' => 0,
                'label' => 'Belum ada data',
                'tone' => 'gray',
                'aspects' => [],
                'weak' => [],
                'advice' => 'Mulai satu sesi latihan sempro untuk mengukur kesiapan Anda.',
            ];
        }

        $aspects = ['concept', 'relevance', 'argumentation', 'methodology', 'clarity', 'confidence'];
        $averages = [];

        foreach ($aspects as $aspect) {
            $values = $evaluations->pluck($aspect)->filter(fn ($v) => $v !== null);
            $averages[$aspect] = $values->count() ? (int) round($values->avg()) : 0;
        }

        $base = (int) round($evaluations->avg('score'));

        /*
         * Cakupan kategori ikut menaikkan/menurunkan skor: jawaban bagus hanya di
         * satu kategori belum berarti siap. Tanpa pembobotan ini skor terlihat
         * lebih tinggi daripada kesiapan sebenarnya.
         */
        $covered = $evaluations->pluck('question.category')->filter()->unique()->count();
        $coverage = min(1.0, $covered / 4); // 4 kategori inti: metodologi, landasan teori, hasil, umum
        $score = (int) round($base * (0.75 + 0.25 * $coverage));

        $weak = $this->weakCategories($project);
        $tone = $score >= 80 ? 'green' : ($score >= 60 ? 'amber' : 'red');
        $label = match ($tone) {
            'green' => 'Siap',
            'amber' => 'Cukup siap',
            default => 'Perlu latihan',
        };

        $advice = $weak
            ? 'Perkuat kategori: '.implode(', ', $weak).'. Latih 5 pertanyaan tambahan di kategori itu.'
            : ($score >= 80
                ? 'Pertahankan. Ulangi satu sesi menjelang hari-H.'
                : 'Latih lagi dengan tingkat kesulitan sulit untuk menaikkan skor.');

        return [
            'score' => $score,
            'label' => $label,
            'tone' => $tone,
            'aspects' => $averages,
            'weak' => $weak,
            'advice' => $advice,
        ];
    }

    /**
     * Pertanyaan lanjutan setelah jawaban lemah pada satu kategori.
     * Ambil dari bank dulu; AI hanya dipakai kalau bank tidak punya sisa pertanyaan.
     *
     * @return array<int, SemproQuestion>
     */
    public function followUp(SemproService $sempro, \App\Models\SemproSession $session, string $category, int $count = 3): array
    {
        $project = $session->project;

        // Pertanyaan yang sudah ada di sesi ini, jangan diulang.
        $asked = $session->questions()->pluck('question')->all();

        $candidates = $this->pick($project, $category, null, $count + count($asked))
            ->reject(fn ($q) => in_array($q->question, $asked, true))
            ->take($count);

        if ($candidates->isEmpty()) {
            // Bank habis untuk kategori ini — minta AI bikin pertanyaan baru.
            return $sempro->generateQuestions($session, $count, $category);
        }

        $position = $session->questions()->max('position') ?? -1;
        $created = [];

        foreach ($candidates as $row) {
            $created[] = SemproQuestion::create([
                'sempro_session_id' => $session->id,
                'project_id' => $project->id,
                'category' => $row->category,
                'difficulty' => $row->difficulty,
                'source' => 'bank',
                'question' => $row->question,
                'expected_points' => $row->expected_points,
                'position' => ++$position,
            ]);
        }

        $session->update(['question_count' => $session->questions()->count()]);

        return $created;
    }

    /** Samakan penulisan metode project dengan nilai di bank. */
    private function normalizeMethod(?string $method): ?string
    {
        if (! $method) {
            return null;
        }

        $key = strtolower(trim($method));

        return match (true) {
            str_contains($key, 'kuantitatif') => 'kuantitatif',
            str_contains($key, 'kualitatif') => 'kualitatif',
            str_contains($key, 'mixed') => 'mixed methods',
            str_contains($key, 'kasus') => 'studi kasus',
            str_contains($key, 'eksperimen') => 'eksperimen',
            default => $key,
        };
    }
}
