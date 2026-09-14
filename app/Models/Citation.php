<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Citation extends Model
{
    protected $fillable = [
        'project_id',
        'reference_id',
        'thesis_section_id',
        'style',
        'in_text',
        'position',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reference(): BelongsTo
    {
        return $this->belongsTo(Reference::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ThesisSection::class, 'thesis_section_id');
    }
}
