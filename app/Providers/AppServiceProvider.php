<?php

namespace App\Providers;

use Anthropic\Client as AnthropicClient;
use App\Services\ReceiptReader;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ReceiptReader::class, fn () => new ReceiptReader(new AnthropicClient(
            apiKey: config('services.anthropic.key'),
            requestOptions: ['timeout' => 90, 'maxRetries' => 2],
        )));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
