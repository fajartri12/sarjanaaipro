<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisTitle extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'description',
        'study_program',
        'topic',
        'object',
        'location',
        'method',
        'keywords',
        'relevance',
        'novelty',
        'feasibility',
        'complexity',
        'gap_score',
        'research_gap',
        'variables',
        'recommendation',
        'risk',
        'is_selected',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_selected' => 'boolean',
            'relevance' => 'integer',
            'novelty' => 'integer',
            'feasibility' => 'integer',
            'complexity' => 'integer',
            'gap_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Skor rata-rata untuk sorting/peringkat alternatif judul. */
    public function averageScore(): int
    {
        $scores = array_filter([
            $this->relevance,
            $this->novelty,
            $this->feasibility,
            $this->gap_score,
        ], fn ($v) => $v !== null);

        return $scores ? (int) round(array_sum($scores) / count($scores)) : 0;
    }
}
