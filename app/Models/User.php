<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STUDENT = 'student';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'university',
        'study_program',
        'degree_level',
        'avatar',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'subscriptions');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function aiUsages()
    {
        return $this->hasMany(AiUsage::class);
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class)->latest();
    }

    public function unreadNotificationsCount(): int
    {
        return $this->hasMany(AppNotification::class)->whereNull('read_at')->count();
    }

    public function conversations()
    {
        return $this->hasMany(AiConversation::class);
    }

    public function titles()
    {
        return $this->hasMany(ThesisTitle::class);
    }

    public function references()
    {
        return $this->hasMany(Reference::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /** Scope: cari berdasarkan nama atau email. */
    public function scopeSearch($query, ?string $term)
    {
        return $query->when($term, fn ($q) => $q->where(function ($sub) use ($term) {
            $sub->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        }));
    }

    /** Scope: hanya role student. */
    public function scopeStudents($query)
    {
        return $query->where('role', self::ROLE_STUDENT);
    }

    /** Scope: user terbaru, untuk listing. */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->latest()->limit($limit);
    }

    /** Pertumbuhan kumulatif pengguna baru per hari, untuk grafik. */
    public static function growthTrend(int $days = 30): array
    {
        $rows = static::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $out = [];
        $running = 0;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $running += (int) ($rows[$date->toDateString()] ?? 0);
            $out[] = ['label' => $date->translatedFormat('j M'), 'value' => $running];
        }

        return $out;
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->whereStatus('active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latestOfMany();
    }

    /** Langganan terakhir apa pun statusnya — dipakai admin untuk memperpanjang yang sudah kedaluwarsa. */
    public function latestSubscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Nama dokumen sesuai jenjang user, mis. "skripsi" / "tesis" / "disertasi". */
    public function documentType(): string
    {
        return \App\Support\Labels::document($this->degree_level);
    }

    /** Versi berjudul, mis. "Skripsi" / "Tesis" / "Disertasi". */
    public function documentTitle(): string
    {
        return \App\Support\Labels::documentTitle($this->degree_level);
    }

    /** Plan aktif saat ini, fallback ke plan Free bawaan. */
    public function currentPlan(): ?Plan
    {
        return $this->activeSubscription?->plan
            ?? Plan::whereSlug(Plan::FREE)->first();
    }
}
