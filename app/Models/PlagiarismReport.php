<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Laporan pengecekan plagiarisme/similarity terhadap draft atau dokumen.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $project_id
 * @property int|null $thesis_section_id
 * @property string $type section|document|full_project
 * @property int $similarity_score 0-100
 * @property array|null $matches
 * @property string|null $summary
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PlagiarismReport extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'thesis_section_id',
        'type',
        'similarity_score',
        'matches',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'matches' => 'array',
            'similarity_score' => 'integer',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(ThesisSection::class, 'thesis_section_id');
    }

    /** Scope: laporan milik user tertentu. */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** Scope: laporan untuk project tertentu. */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /** Scope: laporan untuk satu bagian. */
    public function scopeForSection($query, $sectionId)
    {
        return $query->where('thesis_section_id', $sectionId);
    }

    /**
     * Laporan pengecekan bagian terbaru untuk tiap bagian, di-keyed by section id.
     *
     * unique() mempertahankan kemunculan pertama, jadi urutan id menurun penting:
     * kalau dibalik, yang tersisa justru hasil pemeriksaan paling lama.
     */
    public static function latestPerSection(int $projectId): Collection
    {
        return static::forProject($projectId)
            ->where('type', 'section')
            ->orderByDesc('id')
            ->get()
            ->unique('thesis_section_id')
            ->keyBy('thesis_section_id');
    }

    /** Laporan pengecekan seluruh project yang paling baru. */
    public static function latestFull(int $projectId): ?self
    {
        return static::forProject($projectId)
            ->where('type', 'full_project')
            ->latest('id')
            ->first();
    }
}
