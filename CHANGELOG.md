# Changelog

All notable changes to `larasub` will be documented in this file.

## [2.0.0] - TBD

### ⚠️ BREAKING CHANGES

This version introduces comprehensive plan versioning support and significantly changes how plans, subscriptions, and features are managed. **Please read all breaking changes carefully before upgrading.**

#### Database Changes

**New Tables:**
- Added `plan_versions` table to support plan versioning

**Modified Tables:**
- **plans table**: Removed columns `price`, `currency`, `reset_period`, `reset_period_type`
- **subscriptions table**: 
  - Removed `plan_id` column
  - Added `plan_version_id` column with foreign key constraint to `plan_versions` table
- **plan_features table**:
  - Removed `plan_id` column and its foreign key constraint
  - Added `plan_version_id` column with foreign key constraint to `plan_versions` table
  - Removed unique constraint on `plan_id` and `feature_id` combination
  - Added unique constraint to ensure each feature appears only once per plan version

**Migration Commands:**
- A new migration command `MigrateToPlanVersioningCommand` has been added to migrate existing data
- **Data Migration**: Existing plans are automatically migrated to plan versions with version number 1.0.0
- **Note**: The data migration is not reversible

#### Model Changes

**Plan Model:**
- Removed `Period` enum
- Removed properties: `price`, `currency`, `reset_period`, `reset_period_type`
- Added new `PlanVersion` relationship
- Changed `features()` method to return `HasManyThrough` relationship instead of `HasMany`
- Changed `subscriptions()` method to return `HasManyThrough` relationship instead of `HasMany`
- Updated `feature()` method to load features based on the current version
- Added new methods: `isFree()`, `getPrice()`, `getCurrency()`, `isPublished()`

**PlanFeature Model:**
- Changed property from `plan` to `planVersion`
- Changed `plan_id` to `plan_version_id` in `$fillable` array
- Changed return type of `plan()` method from `BelongsTo<Plan, $this>` to `BelongsTo<PlanVersion, $this>`

**Subscription Model:**
- Changed property name from `plan_id` to `plan_version_id`
- Renamed `plan` relationship method to `planVersion`
- Updated all references from `plan` to `planVersion` in various methods
- Added new scopes: `scopeWherePlanVersion()` and `scopeWhereNotPlanVersion()`
- Updated feature-related methods to use `planVersion` instead of `plan`
- Changed `remainingFeatureUsage()` method to return `FeatureValue` instead of `floatval(INF)` for unlimited features

**Feature Model:**
- Renamed method `plans()` to `planFeatures()`
- Added new method `planVersions()`
- Renamed method `subscriptions()` to `subscriptionFeatureUsages()`
- Added new `subscriptions()` method with `BelongsToMany` relationship

**New PlanVersion Model:**
- Added comprehensive `PlanVersion` model with properties, methods, and relationships
- Handles version-specific plan data including price, currency, reset periods

#### Resource Changes

**PlanResource:**
- Removed fields: `price`, `currency`, `reset_period`, `reset_period_type`, `sort_order`
- Added fields: `current_version`, `versions`

**PlanFeatureResource:**
- Changed `plan` attribute to use `planVersion` relationship
- Updated from `plan` to `plan_version` key

**SubscriptionResource:**
- Changed `plan` key to `plan_version`
- Updated resource instantiation for `plan` key

**New PlanVersionResource:**
- Added new `PlanVersionResource` class in the Resources namespace

#### Service Changes

**PlanService:**
- Changed parameter type from `\Err0r\Larasub\Models\Plan` to `\Err0r\Larasub\Models\PlanVersion` in `getPlanEndAt()` method

**SubscriptionHelperService:**
- The `subscribe()` method now accepts either a `Plan` or `PlanVersion` object as the second parameter
- Changed `plan_id` attribute to `plan_version_id`
- Updated `renewed_from_id` attribute to use `getKey()` method
- Uses `planVersion` attribute instead of `plan` in certain instances

#### Trait Changes

**Subscribable Trait:**
- Added support for subscribing to a specific `PlanVersion` in addition to a `Plan`
- Changed parameter in `subscribe()` method from `$plan` to `$planOrVersion`
- Changed parameter in `subscribed()` method from `$plan` to `$planOrVersion`
- Updated logic to handle checking for `PlanVersion` instance

**Sortable Trait:**
- The `scopeSorted()` method now uses `reorder()` instead of `orderBy()` for sorting
- Added new method `scopeUnsorted()` to remove the sortable global scope

#### Builder Changes

**PlanBuilder:**
- Renamed private attribute `$attributes` to `$planAttributes`
- Added new private attribute `$versionAttributes`
- Added new methods: `versionNumber()`, `versionLabel()`, `versionInactive()`, `published()`
- **Deprecated**: `version()` method
- Modified `build()` method to handle plan versions and features differently

#### Configuration Changes

- Added `plan_versions` configuration in `larasub.php`
- Added `plan_version` model class configuration
- Added `plan_version` resource class configuration

#### Command Changes

- Added new command: `MigrateToPlanVersioningCommand`
- Added new migrations:
  - `create_plan_versions_table`
  - `add_plan_version_id_to_subscriptions_table`
  - `add_plan_version_id_to_plan_features_table`
  - `migrate_existing_data_to_plan_versioning`
  - `drop_versioned_columns_from_plans_table`
  - `drop_plan_id_from_subscriptions_table`
  - `drop_plan_id_from_plan_features_table`

### Migration Guide

1. **Backup your database** before upgrading
2. Run the migration command to transform existing data: `php artisan larasub:migrate-to-versioning`
3. Update your code to use `planVersion` instead of `plan` where applicable
4. Update any custom code that relies on the removed model properties and methods
5. Test thoroughly in a staging environment before deploying to production

### Note on Reversibility

⚠️ **Important**: The data migration to plan versioning is **not reversible**. Once you upgrade to version 2.0.0, you cannot easily downgrade to version 1.x without data loss.
