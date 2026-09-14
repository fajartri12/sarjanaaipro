<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REVISION = 'revision';

    protected $fillable = [
        'project_id',
        'user_id',
        'section_id',
        'score',
        'scores',
        'summary',
        'recommendations',
        'status',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'scores' => 'array',
            'recommendations' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ThesisSection::class);
    }
}