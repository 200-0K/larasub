<?php

namespace Err0r\Larasub\Providers;

use Illuminate\Support\ServiceProvider;

class LarasubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/larasub.php', 'larasub'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__.'/../../config/larasub.php' => config_path('larasub.php'),
            ], 'larasub-config');

            // Publish core migrations
            $this->publishes([
                __DIR__.'/../../database/migrations/create_plans_table.php' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_plans_table.php'),
                __DIR__.'/../../database/migrations/create_subscriptions_table.php' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_subscriptions_table.php'),
            ], 'larasub-migrations');

            // Publish feature migrations (optional)
            if (config('larasub.features.enabled')) {
                $this->publishes([
                    __DIR__.'/../../database/migrations/create_features_tables.php' => database_path('migrations/'.date('Y_m_d_His', time() + 2).'_create_features_tables.php'),
                ], 'larasub-features-migrations');
            }

            // Register commands
            $this->commands([
                \Err0r\Larasub\Commands\CreatePlanCommand::class,
            ]);
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}