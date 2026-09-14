<?php

namespace App\Providers;

use App\Services\AI\AiManager;
use App\Services\AI\AiService;
use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\UsageLimiter;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiManager::class, fn ($app) => new AiManager($app['config']['ai']));
        $this->app->bind(AiProvider::class, fn ($app) => $app->make(AiManager::class)->default());
        $this->app->singleton(AiService::class, fn ($app) => new AiService(
            $app->make(AiManager::class),
            new UsageLimiter,
        ));
        $this->app->singleton(PaymentManager::class, fn ($app) => new PaymentManager($app['config']['payment']));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
