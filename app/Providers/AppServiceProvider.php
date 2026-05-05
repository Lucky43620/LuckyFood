<?php

namespace App\Providers;

use App\Services\FatSecretService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FatSecretService::class, fn (): FatSecretService => new FatSecretService(
            consumerKey: (string) config('fatsecret.consumer_key', ''),
            consumerSecret: (string) config('fatsecret.consumer_secret', ''),
            baseUrl: (string) config('fatsecret.base_url', 'https://platform.fatsecret.com/rest'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
