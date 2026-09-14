<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SemproSession extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'status',
        'score',
        'question_count',
        'summary',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'finished_at' => 'datetime',
            'score' => 'integer',
            'question_count' => 'integer',
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

    public function questions(): HasMany
    {
        return $this->hasMany(SemproQuestion::class)->orderBy('position');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SemproAnswer::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(SemproEvaluation::class);
    }

    /** Scope: sesi milik user tertentu. */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
