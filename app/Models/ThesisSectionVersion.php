<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisSectionVersion extends Model
{
    protected $fillable = [
        'section_id',
        'user_id',
        'content',
        'word_count',
        'reason',
    ];

    protected function casts(): array
    {
        return ['word_count' => 'integer'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ThesisSection::class, 'section_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}