<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reference extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'document_id',
        'type',
        'authors',
        'year',
        'title',
        'container',
        'volume',
        'issue',
        'pages',
        'doi',
        'url',
        'city',
        'publisher',
        'abstract',
        'notes',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Scope: referensi milik user tertentu. */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** Scope: referensi yang project-nya milik user tertentu. */
    public function scopeInUserProjects($query, $userId)
    {
        return $query->whereHas('project', fn ($q) => $q->where('user_id', $userId));
    }

    /** Scope: cari di judul, penulis, atau abstrak. */
    public function scopeSearch($query, ?string $term)
    {
        return $query->when($term, fn ($q) => $q->where(function ($sub) use ($term) {
            $sub->where('title', 'like', "%{$term}%")
                ->orWhere('authors', 'like', "%{$term}%")
                ->orWhere('abstract', 'like', "%{$term}%");
        }));
    }

    /** Scope: hanya referensi yang punya DOI. */
    public function scopeWithDoi($query)
    {
        return $query->whereNotNull('doi');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }

    /** Sitasi dalam teks: (Penulis, 2025) / [1] / (Penulis 2025) */
    public function inTextCitation(string $style, int $number = 1): string
    {
        $first = $this->firstAuthorSurname();
        $year = $this->year ?? 'n.d.';

        return match ($style) {
            'ieee' => "[$number]",
            'harvard' => "({$first} {$year})",
            default => "({$first}, {$year})",
        };
    }

    /** Daftar pustaka sesuai gaya sitasi. */
    public function bibliography(string $style, int $number = 1): string
    {
        $authors = $this->authors ?: 'Anonim';
        $year = $this->year ?? 'n.d.';

        return match ($style) {
            'ieee' => "[$number] {$authors}. ({$year}). {$this->title}. "
                .($this->container ? "{$this->container}, " : '')
                .($this->volume ? "vol. {$this->volume}, " : '')
                .($this->issue ? "no. {$this->issue}, " : '')
                .($this->pages ? "pp. {$this->pages}. " : '')
                .($this->doi ? "doi: {$this->doi}." : ''),

            'harvard' => "{$authors} ({$year}) '{$this->title}', "
                .($this->container ? "{$this->container}, " : '')
                .($this->volume ? "{$this->volume}" : '')
                .($this->issue ? "({$this->issue})" : '')
                .($this->pages ? ", pp. {$this->pages}" : '')
                .($this->publisher ? ". {$this->publisher}" : '').'.',

            default => "{$authors}. ({$year}). {$this->title}. "
                .($this->container ? "{$this->container}, " : '')
                .($this->volume ? "{$this->volume}" : '')
                .($this->issue ? "({$this->issue})" : '')
                .($this->pages ? ", {$this->pages}" : '')
                .($this->doi ? ". https://doi.org/{$this->doi}" : '.'),
        };
    }

    private function firstAuthorSurname(): string
    {
        $first = trim(explode(',', (string) $this->authors)[0]);
        $parts = preg_split('/\s+/', $first);

        return $parts ? end($parts) : 'Anonim';
    }
}
