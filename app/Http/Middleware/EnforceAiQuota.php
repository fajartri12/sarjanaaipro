<?php

namespace App\Http\Middleware;

use App\Services\AI\UsageLimiter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi aksi AI sesuai kuota plan.
 * Dipasang per-route: ->middleware('ai.limit:generate_titles')
 *
 * Ini hanya saringan cepat supaya user tidak menunggu lama sebelum ditolak.
 * Penegakan yang sebenarnya ada di AiService::chat(), di dalam transaksi
 * ber-lock — supaya request bersamaan tidak bisa sama-sama lolos.
 */
class EnforceAiQuota
{
    public function __construct(private UsageLimiter $limiter) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user && ! $this->limiter->allows($user, $feature)) {
            $message = 'Kuota '.str_replace('_', ' ', $feature).' untuk plan Anda sudah habis bulan ini. Upgrade plan untuk melanjutkan.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 429);
            }

            return back()->withErrors(['quota' => $message]);
        }

        return $next($request);
    }
}
