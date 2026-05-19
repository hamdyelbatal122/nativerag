<?php

declare(strict_types=1);

namespace Hamzi\NativeRag;

use Illuminate\Support\ServiceProvider;

class NativeRagServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/nativerag.php',
            'nativerag'
        );

        $this->app->singleton('nativerag', function ($app) {
            return new NativeRagManager($app);
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/nativerag.php' => config_path('nativerag.php'),
            ], 'nativerag-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'nativerag-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
