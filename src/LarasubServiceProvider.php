<?php

namespace Err0r\Larasub;

use Carbon\Carbon;
use Err0r\Larasub\Commands\CheckEndingSubscriptions;
use Err0r\Larasub\Commands\CleanupExpiredCreditsCommand;
use Err0r\Larasub\Commands\LarasubSeed;
use Err0r\Larasub\Commands\MigrateToPlanVersioningCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LarasubServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('larasub')
            ->hasConfigFile()
            ->hasMigrations([
                'create_plans_table',
                'create_plan_versions_table',
                'create_features_table',
                'create_plan_features_table',
                'create_subscriptions_table',
                'create_subscription_feature_usage_table',
                'create_subscription_feature_credits_table',
                'create_events_table',
            ])
            // ->hasTranslations()
            // ->hasCommand(LarasubSeed::class)
            ->hasCommand(CheckEndingSubscriptions::class)
            ->hasCommand(CleanupExpiredCreditsCommand::class)
            ->hasCommand(MigrateToPlanVersioningCommand::class);
    }

    public function packageRegistered(): void
    {
        // Publish versioning migrations with specific tag
        if ($this->app->runningInConsole()) {
            $now = Carbon::now();
            $versioningMigrations = [
                'versioning/add_plan_version_id_to_plan_features_table',
                'versioning/add_plan_version_id_to_subscriptions_table',
                'versioning/migrate_existing_data_to_plan_versioning',
                'versioning/drop_plan_id_from_plan_features_table',
                'versioning/drop_plan_id_from_subscriptions_table',
                'versioning/drop_versioned_columns_from_plans_table',
                'versioning/add_is_hidden_to_plan_features_table',
            ];

            $publishArray = [];
            foreach ($versioningMigrations as $migrationFileName) {
                $filePath = $this->package->basePath("/../database/migrations/{$migrationFileName}.php");
                if (! file_exists($filePath)) {
                    // Support for the .stub file extension
                    $filePath .= '.stub';
                }

                $publishArray[$filePath] = Str::replace('versioning/', '', $this->generateMigrationName(
                    $migrationFileName,
                    $now->addSecond()
                ));
            }

            $this->publishes($publishArray, 'larasub-migrations-upgrade-plan-versioning');
        }
    }

    public function packageBooted(): void
    {
        $this->app->booted(function () {
            /** @var Schedule */
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('larasub:check-ending-subscriptions')
                ->everyMinute()
                ->withoutOverlapping()
                // ->sendOutputTo(storage_path('logs/larasub-check-ending-subscriptions.log'), true)
                ->when(config('larasub.scheduling.enabled'));
        });
    }
}
