<?php

namespace App\Providers;

use App\Http\Integrations\FatSecret\FatSecretNormalizer;
use App\Services\FatSecretService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->bind(FatSecretService::class, fn (): FatSecretService => new FatSecretService(
            normalizer: new FatSecretNormalizer,
            consumerKey: (string) config('fatsecret.consumer_key', ''),
            consumerSecret: (string) config('fatsecret.consumer_secret', ''),
            baseUrl: (string) config('fatsecret.base_url', 'https://platform.fatsecret.com/rest'),
            connectTimeout: (float) config('fatsecret.connect_timeout', 3),
            requestTimeout: (float) config('fatsecret.request_timeout', 8),
            categoryCacheTtl: (int) config('fatsecret.category_cache_ttl', 86400),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('fatsecret', fn (Request $request): Limit => Limit::perMinute(30)->by((string) ($request->user()?->id ?: $request->ip())));
    }
}
