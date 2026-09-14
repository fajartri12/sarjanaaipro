<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AiUsage;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\Providers\ManualProvider;
use App\Services\SubscriptionService;
use App\Support\Labels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function dashboard(): \Illuminate\View\View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'students' => User::students()->count(),
                'projects' => Project::count(),
                'activeSubscriptions' => Subscription::activeCount(),
                'revenue' => Payment::paidTotal(),
                'aiCost' => AiUsage::totalCost(),
                'margin' => Payment::paidTotal() - AiUsage::totalCost(),
                'aiCalls' => AiUsage::count(),
                'pendingPayments' => Payment::pending()->count(),
            ],
            'recentUsers' => User::recent(8)->get(['id', 'name', 'email', 'role', 'created_at']),
            'recentPayments' => Payment::with(['user:id,name', 'plan:id,name'])
                ->latest()
                ->limit(8)
                ->get(),
            'usageByFeature' => AiUsage::featureSummary()->get(),
            'revenueTrend' => Payment::revenueTrend(30),
            'userGrowth' => User::growthTrend(30),
            'planDistribution' => Subscription::planDistribution(),
        ]);
    }

    public function users(Request $request): \Illuminate\View\View
    {
        $users = User::query()
            ->search($request->q)
            ->when($request->role, fn ($q) => $q->where('role', $request->role))
            ->when($request->degree_level, fn ($q) => $q->where('degree_level', $request->degree_level))
            ->withCount('projects')
            ->with(['activeSubscription.plan', 'latestSubscription.plan'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'filters' => $request->only('q', 'role', 'degree_level'),
        ]);
    }

    public function updateUser(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'role' => ['nullable', 'in:admin,student'],
            'is_active' => ['nullable', 'boolean'],
            'degree_level' => ['nullable', Rule::in(array_keys(Labels::DEGREE_LEVEL))],
        ]);

        $user->update(array_filter([
            'role' => $data['role'] ?? null,
            'is_active' => $data['is_active'] ?? null,
            'degree_level' => $data['degree_level'] ?? null,
        ], fn ($v) => $v !== null));

        return back()->with('success', 'Data pengguna diperbarui.');
    }

    public function updateSubscription(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'ends_at' => ['nullable', 'date'],
        ]);

        $subscription = $user->latestSubscription;

        if (! $subscription) {
            return back()->with('error', 'Pengguna belum pernah memiliki langganan.');
        }

        $endsAt = $data['ends_at'] ? \Illuminate\Support\Carbon::parse($data['ends_at']) : null;

        $subscription->update([
            'status' => $endsAt && $endsAt->isFuture() ? 'active' : $subscription->status,
            'ends_at' => $endsAt,
        ]);

        return back()->with('success', 'Tanggal kedaluwarsa langganan diperbarui.');
    }

    public function usage(Request $request): \Illuminate\View\View
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $userId = $data['user_id'] ?? null;

        return view('admin.usage', [
            'usage' => AiUsage::with(['user:id,name,email', 'project:id,name'])
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->latest()
                ->paginate(30)
                ->withQueryString(),
            'totals' => [
                'calls' => AiUsage::count(),
                'tokens' => AiUsage::totalTokens(),
                'cost' => AiUsage::totalCost(),
            ],
        ]);
    }

    /**
     * Monitoring biaya & performa AI: pengguna termahal, fitur terboros,
     * dan apakah provider melambat.
     */
    public function ai(Request $request): \Illuminate\View\View
    {
        $days = (int) $request->input('days', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $since = now()->subDays($days - 1)->startOfDay();

        $scoped = fn () => AiUsage::since($since);

        return view('admin.ai', [
            'days' => $days,
            'totals' => [
                'calls' => (clone $scoped())->count(),
                'tokens' => (int) (clone $scoped())->sum('total_tokens'),
                'cost' => (int) (clone $scoped())->successful()->sum('estimated_cost'),
                'failed' => (clone $scoped())->where('status', '!=', 'success')->count(),
                'avg_ms' => (int) round((float) (clone $scoped())->successful()->avg('response_time')),
            ],
            'byUser' => (clone $scoped())->userSummary(15)->get(),
            'byFeature' => (clone $scoped())->featureDetail()->get(),
            'byModel' => (clone $scoped())->modelSummary()->get(),
            'daily' => AiUsage::dailyTrend($days),
        ]);
    }

    public function payments(Request $request): \Illuminate\View\View
    {
        return view('admin.payments', [
            'payments' => Payment::with(['user:id,name,email', 'plan:id,name'])
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('status'),
            'channels' => collect(ManualProvider::channels())
                ->pluck('name')
                ->filter()
                ->values()
                ->all(),
            'recon' => [
                'paid_total' => Payment::paidTotal(),
                'paid_count' => Payment::paid()->count(),
                'pending_total' => Payment::pendingTotal(),
                'pending_count' => Payment::pending()->count(),
                'expired_count' => Payment::failedOrExpired()->count(),
                'no_invoice' => Payment::withoutInvoice()->count(),
                'stale_days' => 3,
                'stale' => Payment::stale(3)->count(),
            ],
        ]);
    }

    public function confirmPayment(Request $request, Payment $payment): \Illuminate\Http\RedirectResponse
    {
        // Admin boleh mencatat dari rekening mana dana masuk saat konfirmasi.
        $data = $request->validate([
            'channel' => ['nullable', 'string', 'max:100'],
        ]);

        if (! empty($data['channel'])) {
            $payment->update(['channel' => $data['channel']]);
        }

        $this->subscriptions->markAsPaid($payment);

        return back()->with('success', 'Pembayaran dikonfirmasi.');
    }

    /** Kelola rekening tujuan transfer manual. */
    public function settings(): \Illuminate\View\View
    {
        return view('admin.settings', [
            'channels' => ManualProvider::channels(),
            'contact' => ManualProvider::contact(),
        ]);
    }

    public function updateSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'contact' => ['nullable', 'string', 'max:255'],
            'channels' => ['array'],
            'channels.*.name' => ['required', 'string', 'max:60'],
            'channels.*.type' => ['required', Rule::in(['bank', 'ewallet'])],
            // Nomor rekening bisa angka/tanda hubung; kanal bernomor kosong
            // otomatis disembunyikan dari halaman pengguna.
            'channels.*.number' => ['nullable', 'string', 'max:40'],
            'channels.*.holder' => ['nullable', 'string', 'max:100'],
        ]);

        Setting::put('payment_channels', array_values($data['channels'] ?? []));
        Setting::put('payment_contact', $data['contact'] ?? '');

        return back()->with('success', 'Rekening tujuan diperbarui.');
    }

    /** Tandai pembayaran menggantung sebagai kedaluwarsa supaya antrean tidak menumpuk. */
    public function expirePayment(Payment $payment): \Illuminate\Http\RedirectResponse
    {
        abort_unless($payment->status === 'pending', 422, 'Hanya pembayaran menunggu yang bisa dikedaluwarsakan.');

        DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'expired']);
            $payment->subscription?->update(['status' => 'expired']);
        });

        return back()->with('success', 'Pembayaran ditandai kedaluwarsa.');
    }

    public function plans(): \Illuminate\View\View
    {
        return view('admin.plans', [
            'plans' => Plan::orderBy('sort')->get(),
        ]);
    }

    public function updatePlan(Request $request, Plan $plan): \Illuminate\Http\RedirectResponse
    {
        // Form HTML mengirim limits/features sebagai teks, perlu di-decode jadi array.
        if (is_string($request->input('limits'))) {
            $request->merge(['limits' => json_decode($request->input('limits') ?: '[]', true) ?: []]);
        }

        if (is_string($request->input('features'))) {
            $request->merge(['features' => array_values(array_filter(array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', (string) $request->input('features'))
            )))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
            'limits' => ['nullable', 'array'],
            'features' => ['nullable', 'array'],
        ]);

        $plan->update($data);

        return back()->with('success', 'Plan diperbarui.');
    }

    public function projects(Request $request): \Illuminate\View\View
    {
        $projects = Project::with(['user:id,name,email'])
            ->withCount(['sections', 'documents', 'titles', 'references'])
            ->search($request->q)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.projects', [
            'projects' => $projects,
            'filters' => $request->only('q', 'status', 'user_id'),
        ]);
    }

    public function updateProject(Request $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:aktif,selesai,ditunda'],
        ]);

        $project->update(array_filter($data));

        return back()->with('success', 'Status project diperbarui.');
    }

    public function activity(): \Illuminate\View\View
    {
        return view('admin.activity', [
            'logs' => ActivityLog::with('user:id,name')
                ->latest()
                ->paginate(40),
        ]);
    }
}
