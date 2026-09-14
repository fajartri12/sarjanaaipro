<?php

namespace App\Models;

use App\Support\Labels;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'template_id',
        'name',
        'title',
        'study_program',
        'university',
        'advisor',
        'research_type',
        'method',
        'degree_level',
        'status',
        'deadline',
        'deadline_notified_at',
        'progress',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'deadline' => 'date',
            'deadline_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Scope: project milik user tertentu. */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** Scope: project dengan status tertentu (mis. "aktif"). */
    public function scopeByStatus($query, ?string $status)
    {
        return $query->when($status, fn ($q) => $q->where('status', $status));
    }

    /** Scope: cari di nama, judul, atau deskripsi project. */
    public function scopeSearch($query, ?string $term)
    {
        return $query->when($term, fn ($q) => $q->where(function ($sub) use ($term) {
            $sub->where('name', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('study_program', 'like', "%{$term}%");
        }));
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(UniversityTemplate::class, 'template_id');
    }

    /**
     * Format naskah yang dipakai export: template milik project kalau ada,
     * kalau tidak pakai default dari config/template.php.
     */
    public function format(): array
    {
        return $this->template?->resolvedConfig() ?? config('template.defaults', []);
    }

    public function titles(): HasMany
    {
        return $this->hasMany(ThesisTitle::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ThesisSection::class)->orderBy('position');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function references(): HasMany
    {
        return $this->hasMany(Reference::class);
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    public function semproSessions(): HasMany
    {
        return $this->hasMany(SemproSession::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Sisa hari menuju deadline. Negatif berarti sudah lewat. */
    public function daysUntilDeadline(): ?int
    {
        return $this->deadline ? (int) now()->startOfDay()->diffInDays($this->deadline, false) : null;
    }

    /** Nama dokumen sesuai jenjang project, mis. "skripsi" / "tesis" / "disertasi". */
    public function documentType(): string
    {
        return Labels::document($this->degree_level);
    }

    /** Versi berjudul, mis. "Skripsi" / "Tesis" / "Disertasi". */
    public function documentTitle(): string
    {
        return Labels::documentTitle($this->degree_level);
    }

    /**
     * Kerangka BAB sesuai jenjang.
     *
     * S1: proposal lima bab standar (masalah dirinci, ada hipotesis).
     * S2: masalah lebih ringkas; hipotesis opsional, jadi bab dua berhenti di kerangka konseptual.
     * S3: tinjauan pustaka jadi bab tersendiri dan sintesis/novelty eksplisit.
     */
    public static function blankSections(?string $level = null): array
    {
        $level ??= Labels::DEGREE_LEVEL_DEFAULT;

        return match ($level) {
            'S2' => [
                ['1.1', 'BAB I', 'Latar Belakang'],
                ['1.2', 'BAB I', 'Rumusan Masalah'],
                ['1.3', 'BAB I', 'Tujuan Penelitian'],
                ['1.4', 'BAB I', 'Kegunaan Penelitian'],
                ['2.1', 'BAB II', 'Landasan Teori'],
                ['2.2', 'BAB II', 'Penelitian Terdahulu'],
                ['2.3', 'BAB II', 'Kerangka Konseptual'],
                ['3.1', 'BAB III', 'Jenis Penelitian'],
                ['3.2', 'BAB III', 'Objek Penelitian'],
                ['3.3', 'BAB III', 'Populasi & Sampel'],
                ['3.4', 'BAB III', 'Teknik Pengumpulan Data'],
                ['3.5', 'BAB III', 'Teknik Analisis'],
                ['4.1', 'BAB IV', 'Hasil'],
                ['4.2', 'BAB IV', 'Pembahasan'],
                ['5.1', 'BAB V', 'Kesimpulan'],
                ['5.2', 'BAB V', 'Saran'],
            ],
            'S3' => [
                ['1.1', 'BAB I', 'Latar Belakang'],
                ['1.2', 'BAB I', 'Rumusan Masalah'],
                ['1.3', 'BAB I', 'Tujuan Penelitian'],
                ['1.4', 'BAB I', 'Kegunaan Penelitian'],
                ['2.1', 'BAB II', 'State of the Art'],
                ['2.2', 'BAB II', 'Kajian Teori'],
                ['2.3', 'BAB II', 'Penelitian Terdahulu'],
                ['2.4', 'BAB II', 'Kerangka Berpikir'],
                ['2.5', 'BAB II', 'Kebaruan (Novelty)'],
                ['3.1', 'BAB III', 'Pendekatan Penelitian'],
                ['3.2', 'BAB III', 'Objek & Konteks Penelitian'],
                ['3.3', 'BAB III', 'Sumber Data'],
                ['3.4', 'BAB III', 'Teknik Pengumpulan Data'],
                ['3.5', 'BAB III', 'Teknik Analisis'],
                ['3.6', 'BAB III', 'Validitas & Reliabilitas'],
                ['4.1', 'BAB IV', 'Hasil'],
                ['4.2', 'BAB IV', 'Pembahasan'],
                ['5.1', 'BAB V', 'Sintesis Temuan'],
                ['5.2', 'BAB V', 'Kontribusi Teoretis'],
                ['5.3', 'BAB V', 'Keterbatasan'],
                ['5.4', 'BAB V', 'Rekomendasi'],
            ],
            default => [
                ['1.1', 'BAB I', 'Latar Belakang'],
                ['1.2', 'BAB I', 'Identifikasi Masalah'],
                ['1.3', 'BAB I', 'Batasan Masalah'],
                ['1.4', 'BAB I', 'Rumusan Masalah'],
                ['1.5', 'BAB I', 'Tujuan Penelitian'],
                ['1.6', 'BAB I', 'Manfaat Penelitian'],
                ['2.1', 'BAB II', 'Landasan Teori'],
                ['2.2', 'BAB II', 'Penelitian Terdahulu'],
                ['2.3', 'BAB II', 'Kerangka Konseptual'],
                ['2.4', 'BAB II', 'Hipotesis'],
                ['3.1', 'BAB III', 'Jenis Penelitian'],
                ['3.2', 'BAB III', 'Objek Penelitian'],
                ['3.3', 'BAB III', 'Populasi & Sampel'],
                ['3.4', 'BAB III', 'Teknik Pengumpulan Data'],
                ['3.5', 'BAB III', 'Variabel'],
                ['3.6', 'BAB III', 'Teknik Analisis'],
                ['4.1', 'BAB IV', 'Hasil'],
                ['4.2', 'BAB IV', 'Pembahasan'],
                ['5.1', 'BAB V', 'Kesimpulan'],
                ['5.2', 'BAB V', 'Saran'],
            ],
        };
    }

    /** Buat kerangka BAB saat project pertama kali dibuat, sesuai jenjang. */
    public function seedSections(): void
    {
        if ($this->sections()->exists()) {
            return;
        }

        foreach (self::blankSections($this->degree_level) as $i => [$key, $chapter, $title]) {
            $this->sections()->create([
                'key' => $key,
                'chapter' => $chapter,
                'title' => $title,
                'position' => $i,
            ]);
        }
    }

    public function syncProgress(): void
    {
        $sections = $this->sections()->get();
        $done = $sections->where('status', 'done')->count();

        $this->update([
            'progress' => $sections->isEmpty() ? 0 : (int) round($done / $sections->count() * 100),
        ]);
    }
}
