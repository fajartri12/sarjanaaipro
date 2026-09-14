<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AI\UsageLimiter;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private UsageLimiter $limiter) {}

    public function index(Request $request): \Illuminate\View\View
    {
        $user = $request->user();

        $projects = $user->projects()
            ->withCount(['sections', 'references'])
            ->latest()
            ->get();

        $active = $projects->firstWhere('status', 'aktif') ?? $projects->first();

        $showOnboarding = $request->session()->get('onboarding_dismissed') !== true
            && ($projects->count() === 0 || ($active && $active->sections_count === 0 && $active->references_count === 0));

        $stats = [
            'projects' => $projects->count(),
            'sectionsDone' => $user->projects()->withCount('sections')->get()->sum('sections_count'),
            'references' => $user->projects()->withCount('references')->get()->sum('references_count'),
            'semproSessions' => $user->projects()->withCount('semproSessions')->get()->sum('sempro_sessions_count'),
            'sectionsWritten' => \App\Models\ThesisSection::ownedBy($user->id)->where('status', 'done')->count(),
        ];

        return view('dashboard', [
            'activeProject' => $active,
            'projects' => $projects->take(5)->values(),
            'stats' => $stats,
            'quota' => [
                'generate_titles' => $this->quota($user, 'generate_titles'),
                'ai_chat' => $this->quota($user, 'ai_chat'),
                'projects' => $this->quota($user, 'projects'),
            ],
            'quotaSummary' => [
                'tokens' => (int) $user->aiUsages()->thisMonth()->sum('total_tokens'),
                'cost' => (int) $user->aiUsages()->successful()->thisMonth()->sum('estimated_cost'),
            ],
            'plan' => $user->currentPlan()?->only(['name', 'slug', 'price']),
            'activities' => $this->activities($user),
            'showOnboarding' => $showOnboarding,
            'milestones' => $this->milestones($active, $stats),
            'checklist' => $this->checklist($active, $stats),
        ]);
    }

    /**
     * Lencana pencapaian. Hanya yang sudah tercapai yang dikirim ke view —
     * daftar kosong berarti belum ada apa-apa untuk dirayakan.
     */
    private function milestones(?Project $active, array $stats): array
    {
        $chaptersDone = $active ? $this->chaptersDone($active) : [];

        $all = [
            ['label' => 'Draft pertama', 'icon' => 'document', 'done' => $stats['sectionsDone'] >= 1],
            ['label' => 'BAB I selesai', 'icon' => 'check', 'done' => in_array('BAB I', $chaptersDone, true)],
            ['label' => '10 referensi', 'icon' => 'link', 'done' => $stats['references'] >= 10],
            ['label' => 'Latihan sempro', 'icon' => 'cap', 'done' => $stats['semproSessions'] >= 1],
            ['label' => 'Naskah lengkap', 'icon' => 'sparkles', 'done' => $active && (int) $active->progress >= 100],
        ];

        return array_values(array_filter($all, fn ($m) => $m['done']));
    }

    /** Tahapan menuju sempro, dipakai sebagai checklist di dashboard. */
    private function checklist(?Project $active, array $stats): array
    {
        $chaptersDone = $active ? $this->chaptersDone($active) : [];
        $progress = $active ? (int) $active->progress : 0;

        return [
            ['label' => 'BAB I selesai', 'hint' => 'Latar belakang sampai tujuan', 'done' => in_array('BAB I', $chaptersDone, true)],
            ['label' => 'BAB II selesai', 'hint' => 'Tinjauan pustaka & kerangka', 'done' => in_array('BAB II', $chaptersDone, true)],
            ['label' => 'BAB III selesai', 'hint' => 'Metode penelitian', 'done' => in_array('BAB III', $chaptersDone, true)],
            ['label' => 'Referensi cukup', 'hint' => 'Minimal 10 sumber', 'done' => $stats['references'] >= 10],
            ['label' => 'Naskah lengkap', 'hint' => 'Semua bagian terisi', 'done' => $progress >= 100],
            ['label' => 'Latihan sempro', 'hint' => 'Coba simulasi pertanyaan', 'done' => $stats['semproSessions'] >= 1],
        ];
    }

    /** Daftar BAB yang seluruh sub-babnya sudah berstatus selesai. */
    private function chaptersDone(Project $project): array
    {
        return $project->sections()
            ->get(['chapter', 'status'])
            ->groupBy('chapter')
            ->filter(fn ($rows) => $rows->every(fn ($s) => $s->status === 'done'))
            ->keys()
            ->all();
    }

    private function quota($user, string $feature): array
    {
        $limit = $this->limiter->limit($user, $feature);
        $used = $this->limiter->used($user, $feature);

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit === null ? null : max(0, $limit - $used),
            'resets_at' => now()->endOfMonth(),
        ];
    }

    private function activities($user): array
    {
        return \App\Models\ActivityLog::ownedBy($user->id)
            ->latest()
            ->take(8)
            ->get(['action', 'description', 'created_at'])
            ->map(fn ($log) => [
                'action' => $log->action,
                'description' => $log->description,
                'at' => $log->created_at->diffForHumans(),
            ])
            ->all();
    }

    public function dismissOnboarding(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->session()->put('onboarding_dismissed', true);

        return back();
    }
}
