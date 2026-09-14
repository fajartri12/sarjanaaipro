<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'author',
        'year',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'pages',
        'extracted_text',
        'metadata',
        'status',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'year' => 'integer',
            'size' => 'integer',
            'pages' => 'integer',
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

    /** Scope: dokumen milik user tertentu. */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** Scope: dokumen yang project-nya milik user tertentu. */
    public function scopeInUserProjects($query, $userId)
    {
        return $query->whereHas('project', fn ($q) => $q->where('user_id', $userId));
    }

    /** Scope: cari di judul, penulis, atau teks hasil ekstraksi. */
    public function scopeSearch($query, ?string $term)
    {
        return $query->when($term, fn ($q) => $q->where(function ($sub) use ($term) {
            $sub->where('title', 'like', "%{$term}%")
                ->orWhere('author', 'like', "%{$term}%")
                ->orWhere('extracted_text', 'like', "%{$term}%");
        }));
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class)->orderBy('position');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}
