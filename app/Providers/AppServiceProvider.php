<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\Repositories\NoteRepositoryInterface::class,
            \App\Repositories\NoteRepository::class
        );
        
        $this->app->bind(
            \App\Contracts\AI\SummarizerInterface::class,
            \App\Services\AI\GeminiSummarizer::class
        );
        
        $this->app->bind(
            \App\Contracts\AI\EmbeddingGeneratorInterface::class,
            \App\Services\AI\GeminiEmbeddingGenerator::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('ai-endpoints', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip());
        });
    }
}
