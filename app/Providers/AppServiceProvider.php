<?php

namespace App\Providers;

use App\Interfaces\ApiClient;
use App\Services\TypeScriptApiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ApiClient::class, function () {
            return new TypeScriptApiClient(
                baseUrl: config('services.typescript_api.url'),
                apiAccessKey: config('services.typescript_api.access_key'),
                apiSecretKey: config('services.typescript_api.secret_key'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
