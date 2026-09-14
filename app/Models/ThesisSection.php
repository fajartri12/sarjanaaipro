<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThesisSection extends Model
{
    protected $fillable = [
        'project_id',
        'key',
        'chapter',
        'title',
        'content',
        'word_count',
        'status',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'word_count' => 'integer',
            'position' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Scope: bagian yang project-nya milik user tertentu. */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->whereHas('project', fn ($q) => $q->where('user_id', $userId));
    }

    /** Scope: cari di judul, isi, atau bab. */
    public function scopeSearch($query, ?string $term)
    {
        return $query->when($term, fn ($q) => $q->where(function ($sub) use ($term) {
            $sub->where('title', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%")
                ->orWhere('chapter', 'like', "%{$term}%");
        }));
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ThesisSectionVersion::class, 'section_id')->latest();
    }

    /** HTML dari editor dihitung sebagai kata biasa, bukan karakter markup. */
    public function recalculate(): void
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->content)));

        $this->word_count = $text === '' ? 0 : count(explode(' ', $text));
        $this->status = $this->word_count === 0 ? 'empty' : 'draft';

        if ($this->word_count >= 150) {
            $this->status = 'done';
        }

        $this->save();
    }
}
