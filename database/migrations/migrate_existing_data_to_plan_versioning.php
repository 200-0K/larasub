<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration assumes:
        // 1. plan_versions table already exists
        // 2. plan_version_id columns have been added to subscriptions and plan_features tables
        // 3. Original columns still exist in plans table (will be dropped later)
        
        $this->migrateExistingPlansToVersions();
        $this->updateExistingSubscriptions();
        $this->updateExistingPlanFeatures();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it involves data transformation
        throw new \Exception('This migration cannot be reversed. Please restore from backup if needed.');
    }

    /**
     * Create plan versions from existing plans
     */
    private function migrateExistingPlansToVersions(): void
    {
        $plansTable = config('larasub.tables.plans.name');
        $planVersionsTable = config('larasub.tables.plan_versions.name');
        
        // Skip if necessary tables don't exist
        if (!Schema::hasTable($plansTable)) {
            echo "Skipping plan version migration: Plans table does not exist.\n";
            return;
        }
        
        if (!Schema::hasTable($planVersionsTable)) {
            echo "Skipping plan version migration: Plan versions table does not exist.\n";
            return;
        }

        // Check if the plans table still has the old columns
        if (Schema::hasColumns($plansTable, ['price', 'currency', 'reset_period', 'reset_period_type', 'sort_order'])) {
            // Get all existing plans
            $plans = DB::table($plansTable)->get();
            
            if ($plans->isEmpty()) {
                echo "No plans found to migrate to plan versions.\n";
                return;
            }

            foreach ($plans as $plan) {
                // Create a version 1.0.0 for each existing plan
                DB::table($planVersionsTable)->insert([
                    'id' => Str::uuid(),
                    'plan_id' => $plan->id,
                    'version_number' => 1,
                    'version_label' => '1.0.0',
                    'price' => $plan->price ?? 0.0,
                    'currency' => $plan->currency ?? json_encode('USD'),
                    'reset_period' => $plan->reset_period,
                    'reset_period_type' => $plan->reset_period_type,
                    'is_active' => true,
                    'published_at' => now(),
                    'created_at' => $plan->created_at ?? now(),
                    'updated_at' => $plan->updated_at ?? now(),
                ]);
            }

            echo "Migrated " . count($plans) . " plans to plan versions.\n";
        } else {
            echo "Skipping plan version migration: Plans table does not have the required columns or migration already completed.\n";
        }
    }

    /**
     * Update existing subscriptions to reference plan versions
     */
    private function updateExistingSubscriptions(): void
    {
        $subscriptionsTable = config('larasub.tables.subscriptions.name');
        $planVersionsTable = config('larasub.tables.plan_versions.name');
        
        // Skip if necessary tables don't exist
        if (!Schema::hasTable($subscriptionsTable)) {
            echo "Skipping subscription update: Subscriptions table does not exist.\n";
            return;
        }
        
        if (!Schema::hasTable($planVersionsTable)) {
            echo "Skipping subscription update: Plan versions table does not exist.\n";
            return;
        }
        
        // Skip if necessary columns don't exist
        if (!Schema::hasColumn($subscriptionsTable, 'plan_version_id')) {
            echo "Skipping subscription update: Subscriptions table does not have plan_version_id column.\n";
            return;
        }

        // Update existing subscriptions to reference the plan version
        if (Schema::hasColumn($subscriptionsTable, 'plan_id')) {
            $subscriptions = DB::table($subscriptionsTable)
                ->whereNull('plan_version_id')
                ->get();
                
            if ($subscriptions->isEmpty()) {
                echo "No subscriptions found that need updating.\n";
                return;
            }

            $updateCount = 0;
            foreach ($subscriptions as $subscription) {
                // Find the corresponding plan version (should be version 1.0.0)
                $planVersion = DB::table($planVersionsTable)
                    ->where('plan_id', $subscription->plan_id)
                    ->where('version_number', 1)
                    ->first();

                if ($planVersion) {
                    DB::table($subscriptionsTable)
                        ->where('id', $subscription->id)
                        ->update(['plan_version_id' => $planVersion->id]);
                    $updateCount++;
                }
            }

            echo "Updated " . $updateCount . " of " . count($subscriptions) . " subscriptions to reference plan versions.\n";
        } else {
            echo "Skipping subscription update: Subscriptions table does not have plan_id column or migration already completed.\n";
        }
    }

    /**
     * Update existing plan features to reference plan versions
     */
    private function updateExistingPlanFeatures(): void
    {
        $planFeaturesTable = config('larasub.tables.plan_features.name');
        $planVersionsTable = config('larasub.tables.plan_versions.name');
        
        // Skip if necessary tables don't exist
        if (!Schema::hasTable($planFeaturesTable)) {
            echo "Skipping plan features update: Plan features table does not exist.\n";
            return;
        }
        
        if (!Schema::hasTable($planVersionsTable)) {
            echo "Skipping plan features update: Plan versions table does not exist.\n";
            return;
        }
        
        // Skip if necessary columns don't exist
        if (!Schema::hasColumn($planFeaturesTable, 'plan_version_id')) {
            echo "Skipping plan features update: Plan features table does not have plan_version_id column.\n";
            return;
        }

        // Update existing plan features to reference the plan version
        if (Schema::hasColumn($planFeaturesTable, 'plan_id')) {
            $planFeatures = DB::table($planFeaturesTable)
                ->whereNull('plan_version_id')
                ->get();
                
            if ($planFeatures->isEmpty()) {
                echo "No plan features found that need updating.\n";
                return;
            }

            $updateCount = 0;
            foreach ($planFeatures as $planFeature) {
                // Find the corresponding plan version (should be version 1.0.0)
                $planVersion = DB::table($planVersionsTable)
                    ->where('plan_id', $planFeature->plan_id)
                    ->where('version_number', 1)
                    ->first();

                if ($planVersion) {
                    DB::table($planFeaturesTable)
                        ->where('id', $planFeature->id)
                        ->update(['plan_version_id' => $planVersion->id]);
                    $updateCount++;
                }
            }

            echo "Updated " . $updateCount . " of " . count($planFeatures) . " plan features to reference plan versions.\n";
        } else {
            echo "Skipping plan features update: Plan features table does not have plan_id column or migration already completed.\n";
        }
    }
}; 