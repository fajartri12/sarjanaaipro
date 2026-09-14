<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function prices(Request $request): \Illuminate\View\View
    {
        return view('subscription.prices', [
            'plans' => Plan::active()->get(),
            'currentPlan' => $request->user()->currentPlan(),
            'subscription' => $request->user()->activeSubscription,
        ]);
    }

    public function mySubscription(Request $request): \Illuminate\View\View
    {
        $user = $request->user();

        return view('subscription.my', [
            'currentPlan' => $user->currentPlan(),
            'subscriptions' => $user->subscriptions()
                ->with('plan')
                ->latest()
                ->paginate(10),
            'payments' => $user->payments()->with('plan')->latest()->limit(10)->get(),
            'usage' => $user->aiUsages()->selectRaw('feature, sum(total_tokens) as tokens, count(*) as calls')
                ->groupBy('feature')
                ->get(),
            'manualInstructions' => $this->subscriptions->pendingManualInstructions($user),
        ]);
    }

    /** Mulai checkout ke plan tertentu. */
    public function checkout(Request $request, Plan $plan): \Illuminate\Http\RedirectResponse
    {
        abort_if(! $plan->is_active, 404);
        abort_if($plan->slug === Plan::FREE, 422, 'Plan Free tidak perlu checkout.');

        $result = $this->subscriptions->checkout($request->user(), $plan);

        if ($result['checkout']['redirect_url'] ?? null) {
            return redirect()->away($result['checkout']['redirect_url']);
        }

        return redirect()->route('subscription.my')
            ->with('success', 'Pesanan dibuat. Transfer sesuai nominal ke rekening tujuan, lalu kirim bukti ke admin.');
    }

    /** Catat kanal yang dipilih pengguna untuk pembayaran manual. */
    public function updateChannel(Request $request, Payment $payment): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'max:100'],
        ]);

        abort_unless($payment->user_id === $request->user()->id, 403);
        abort_unless($payment->status === 'pending', 422, 'Hanya pembayaran menunggu yang bisa diubah.');

        $payment->update(['channel' => $data['channel']]);

        return back();
    }

    public function cancel(Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->subscriptions->cancel($request->user());

        return back()->with('success', 'Langganan dibatalkan.');
    }

    /** Webhook pembayaran dari Midtrans/Xendit. */
    public function webhook(Request $request, string $provider): \Illuminate\Http\JsonResponse
    {
        $payload = $request->json()->all();
        // HeaderBag memberi array per nama header; provider mengharapkan nilai tunggal.
        $headers = array_map(fn ($values) => $values[0] ?? '', $request->headers->all());

        try {
            $this->subscriptions->handleWebhook($provider, $payload, $headers);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['ok' => true]);
    }

    /** Konfirmasi manual oleh admin (provider manual). */
    public function confirmPayment(Request $request, Payment $payment): \Illuminate\Http\RedirectResponse
    {
        $this->subscriptions->markAsPaid($payment);

        return back()->with('success', 'Pembayaran dikonfirmasi, langganan diaktifkan.');
    }
}