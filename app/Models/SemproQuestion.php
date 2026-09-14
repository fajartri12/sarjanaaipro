<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemproQuestion extends Model
{
    protected $fillable = [
        'sempro_session_id',
        'project_id',
        'category',
        'difficulty',
        'question',
        'expected_points',
        'position',
        'source',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SemproSession::class, 'sempro_session_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
