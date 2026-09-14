<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemproEvaluation extends Model
{
    protected $fillable = [
        'sempro_session_id',
        'sempro_question_id',
        'sempro_answer_id',
        'concept',
        'relevance',
        'argumentation',
        'methodology',
        'clarity',
        'confidence',
        'score',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'concept' => 'integer',
            'relevance' => 'integer',
            'argumentation' => 'integer',
            'methodology' => 'integer',
            'clarity' => 'integer',
            'confidence' => 'integer',
            'score' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SemproSession::class, 'sempro_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SemproQuestion::class, 'sempro_question_id');
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(SemproAnswer::class, 'sempro_answer_id');
    }
}
