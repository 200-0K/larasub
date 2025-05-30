<?php

namespace Err0r\Larasub\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateToPlanVersioningCommand extends Command
{
    protected $signature = 'larasub:migrate-to-versioning 
                            {--dry-run : Show what would be migrated without making changes}
                            {--force : Force migration without confirmation}';

    protected $description = 'Migrate existing plan data to the new plan versioning system';

    public function handle(): int
    {
        $this->info('🚀 Laravel Subscription Plan Versioning Migration');
        $this->newLine();

        // Check if migration is needed
        if (! $this->migrationNeeded()) {
            $this->info('✅ No migration needed. Your data is already using plan versioning.');

            return self::SUCCESS;
        }

        // Show what will be migrated
        $this->showMigrationSummary();

        // Confirm migration unless forced
        if (! $this->option('force') && ! $this->option('dry-run')) {
            if (! $this->confirm('Do you want to proceed with the migration?')) {
                $this->info('Migration cancelled.');

                return self::SUCCESS;
            }
        }

        if ($this->option('dry-run')) {
            $this->info('🔍 Dry run completed. No changes were made.');

            return self::SUCCESS;
        }

        // Perform migration by running the specific migrations
        $this->performMigration();

        $this->newLine();
        $this->info('✅ Migration completed successfully!');
        $this->info('📝 Don\'t forget to update your code to use the new versioning system.');

        return self::SUCCESS;
    }

    private function migrationNeeded(): bool
    {
        $plansTable = config('larasub.tables.plans.name');
        $planVersionsTable = config('larasub.tables.plan_versions.name');

        // Check if plan_versions table exists
        if (! Schema::hasTable($planVersionsTable)) {
            return true;
        }

        // Check if plans table still has old columns
        if (Schema::hasColumns($plansTable, ['price', 'currency', 'reset_period'])) {
            return true;
        }

        // Check if there are plans without versions
        $plansWithoutVersions = DB::table($plansTable)
            ->leftJoin($planVersionsTable, $plansTable.'.id', '=', $planVersionsTable.'.plan_id')
            ->whereNull($planVersionsTable.'.id')
            ->count();

        return $plansWithoutVersions > 0;
    }

    private function showMigrationSummary(): void
    {
        $plansTable = config('larasub.tables.plans.name');
        $subscriptionsTable = config('larasub.tables.subscriptions.name');
        $planFeaturesTable = config('larasub.tables.plan_features.name');

        $plansCount = DB::table($plansTable)->count();
        $subscriptionsCount = DB::table($subscriptionsTable)->count();
        $planFeaturesCount = DB::table($planFeaturesTable)->count();

        $this->info('📊 Migration Summary:');
        $this->table(
            ['Item', 'Count', 'Action'],
            [
                ['Plans', $plansCount, 'Convert to plan versions (v1.0.0)'],
                ['Subscriptions', $subscriptionsCount, 'Update to reference plan versions'],
                ['Plan Features', $planFeaturesCount, 'Update to reference plan versions'],
            ]
        );
        $this->newLine();

        $this->warn('⚠️  This migration will run the following steps:');
        $this->line('   1. Create plan_versions table (if not exists)');
        $this->line('   2. Add plan_version_id columns to subscriptions and plan_features');
        $this->line('   3. Migrate existing data to plan versions');
        $this->line('   4. Drop old columns from plans table');
        $this->line('   5. Drop old plan_id columns from subscriptions and plan_features');
        $this->newLine();
    }

    private function performMigration(): void
    {
        $this->info('🔄 Starting migration using Laravel migrations...');

        try {
            // Run the specific migrations in order
            $this->info('  📋 Running plan versioning migrations...');

            // This will run all pending migrations including our versioning ones
            $this->call('migrate', [
                '--force' => true,
                '--step' => true,
            ]);

            $this->info('  ✅ All migrations completed successfully');

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: '.$e->getMessage());
            $this->error('💡 You may need to restore from backup and check your migration files.');
            $this->error('💡 Run "php artisan migrate:status" to check migration status.');
            throw $e;
        }
    }
}
