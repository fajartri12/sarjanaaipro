<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    protected $fillable = [
        'document_id',
        'position',
        'content',
        'tokens',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'position' => 'integer',
            'tokens' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
