<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemproAnswer extends Model
{
    protected $fillable = [
        'sempro_session_id',
        'sempro_question_id',
        'answer',
        'position',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SemproSession::class, 'sempro_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SemproQuestion::class, 'sempro_question_id');
    }
}
