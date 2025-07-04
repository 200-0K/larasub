<?php

namespace Err0r\Larasub\Providers;

use Err0r\Larasub\Core\Commands\CreatePlanCommand;
use Illuminate\Support\ServiceProvider;

class LarasubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/larasub.php', 
            'larasub'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfig();
            $this->publishMigrations();
            $this->registerCommands();
        }
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../../config/larasub.php' => config_path('larasub.php'),
        ], 'larasub-config');
    }

    /**
     * Publish the migration files.
     */
    protected function publishMigrations(): void
    {
        $timestamp = date('Y_m_d_His');
        
        // Core migrations
        $this->publishes([
            __DIR__.'/../../database/migrations/create_plans_table.php' => 
                database_path("migrations/{$timestamp}_create_plans_table.php"),
            __DIR__.'/../../database/migrations/create_subscriptions_table.php' => 
                database_path("migrations/" . date('Y_m_d_His', time() + 1) . "_create_subscriptions_table.php"),
        ], 'larasub-migrations');

        // Optional feature migrations
        if (config('larasub.features.enabled', false)) {
            $this->publishes([
                __DIR__.'/../../database/migrations/create_features_table.php' => 
                    database_path("migrations/" . date('Y_m_d_His', time() + 2) . "_create_features_table.php"),
                __DIR__.'/../../database/migrations/create_feature_usage_table.php' => 
                    database_path("migrations/" . date('Y_m_d_His', time() + 3) . "_create_feature_usage_table.php"),
            ], 'larasub-features-migrations');
        }
    }

    /**
     * Register the package commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            CreatePlanCommand::class,
        ]);
    }
}