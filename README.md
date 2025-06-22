# Laravel Subscription Package (Larasub)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/err0r/larasub.svg?style=flat-square)](https://packagist.org/packages/err0r/larasub)
[![Total Downloads](https://img.shields.io/packagist/dt/err0r/larasub.svg?style=flat-square)](https://packagist.org/packages/err0r/larasub)

A powerful and flexible subscription management system for Laravel applications with comprehensive plan versioning support.

## ✨ Features

**Core Subscription Management**
- 📦 Multi-tiered subscription plans with versioning
- 🔄 Flexible billing periods (minute/hour/day/week/month/year)
- 💳 Subscribe users with custom dates and pending status
- 🔄 Cancel, resume, and renew subscriptions
- 📈 Comprehensive subscription lifecycle tracking

**Advanced Feature System**
- 🎯 Feature-based access control (consumable & non-consumable)
- 📊 Usage tracking with configurable limits
- ⏰ Period-based feature resets
- 🔋 Unlimited usage support
- 🔍 Feature usage monitoring and quotas

**Plan Versioning & Management**
- 📋 Plan versioning for seamless updates
- 🔄 Backward compatibility for existing subscribers
- 📅 Historical pricing and feature tracking
- 🚀 Easy rollback capabilities
- 📊 Version-specific analytics

**Developer Experience**
- 🧩 Simple trait-based integration
- ⚙️ Configurable tables and models
- 📝 Comprehensive event system
- 🔌 UUID support out of the box
- 🌐 Multi-language support (translatable plans/features)
- 🛠️ Rich builder pattern APIs

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Migration from v2.x to v3.x](#migration-from-v2x-to-v3x-plan-versioning)
- [Core Concepts](#core-concepts)
- [Subscription Management](#subscription-management)
- [Feature Management](#feature-management)
- [Plan Versioning](#plan-versioning)
- [Events & Lifecycle](#events--lifecycle)
- [API Resources](#api-resources)
- [Configuration](#configuration)
- [Commands](#commands)
- [Testing](#testing)
- [Contributing](#contributing)

## Installation

Install via Composer:

```bash
composer require err0r/larasub
```

Publish configuration:

```bash
php artisan vendor:publish --tag="larasub-config"
```

Run migrations:

```bash
# Publish all migrations
php artisan vendor:publish --tag="larasub-migrations"
php artisan migrate
```

## Quick Start

### 1. Setup Your User Model

```php
<?php

namespace App\Models;

use Err0r\Larasub\Traits\HasSubscription;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasSubscription;
    
    // Your existing model code...
}
```

### 2. Create Features

```php
<?php

use Err0r\Larasub\Builders\FeatureBuilder;

// Consumable feature (trackable usage)
$apiCalls = FeatureBuilder::create('api-calls')
    ->name(['en' => 'API Calls', 'ar' => 'مكالمات API'])
    ->description(['en' => 'Number of API calls allowed'])
    ->consumable()
    ->sortOrder(1)
    ->build();

// Non-consumable feature (boolean access)
$prioritySupport = FeatureBuilder::create('priority-support')
    ->name(['en' => 'Priority Support'])
    ->description(['en' => 'Access to priority customer support'])
    ->nonConsumable()
    ->sortOrder(2)
    ->build();
```

### 3. Create Plans with Versioning

```php
<?php

use Err0r\Larasub\Builders\PlanBuilder;
use Err0r\Larasub\Enums\Period;
use Err0r\Larasub\Enums\FeatureValue;

// Create initial plan version
$premiumPlan = PlanBuilder::create('premium')
    ->name(['en' => 'Premium Plan', 'ar' => 'خطة مميزة'])
    ->description(['en' => 'Access to premium features'])
    ->sortOrder(2)
    ->versionLabel('1.0.0')
    ->price(99.99, 'USD')
    ->resetPeriod(1, Period::MONTH)
    ->published()
    ->addFeature('api-calls', fn ($feature) => $feature
        ->value(1000)
        ->resetPeriod(1, Period::DAY)
        ->displayValue(['en' => '1000 API Calls'])
        ->sortOrder(1)
    )
    ->addFeature('priority-support', fn ($feature) => $feature
        ->value(FeatureValue::UNLIMITED)
        ->displayValue(['en' => 'Priority Support Included'])
        ->sortOrder(2)
    )
    ->build();
```

### 4. Subscribe Users

```php
<?php

// Get plan (automatically uses latest published version)
$plan = Plan::slug('premium')->first();

// Subscribe user
$subscription = $user->subscribe($plan);

// Subscribe with custom dates
$subscription = $user->subscribe($plan, 
    startAt: now(), 
    endAt: now()->addYear()
);

// Create pending subscription (useful for payment processing)
$subscription = $user->subscribe($plan, pending: true);
```

### 5. Check Features & Usage

```php
<?php

// Check feature access
if ($user->hasFeature('priority-support')) {
    // User has access to priority support
}

// Check consumable feature usage
if ($user->canUseFeature('api-calls', 5)) {
    // User can make 5 API calls
    $user->useFeature('api-calls', 5);
}

// Get remaining usage
$remaining = $user->remainingFeatureUsage('api-calls');
```

## Migration from v2.x to v3.x (Plan Versioning)

If upgrading from v2.x, follow these steps:

### 1. Backup Your Database
```bash
# MySQL example
mysqldump -u username -p database_name > backup.sql
```

### 2. Update Package & Run Migration
```bash
composer update err0r/larasub

# See a summary of required changes without affecting the database
php artisan larasub:migrate-to-versioning --dry-run

php artisan vendor:publish --tag="larasub-migrations-upgrade-plan-versioning"
php artisan migrate
```

### 3. Update Your Code

**Before (v2.x):**
```php
// Accessing plan properties directly
$price = $subscription->plan->price;
$features = $subscription->plan->features;
```

**After (v3.x):**
```php
// Access through plan version
$price = $subscription->planVersion->price;
$features = $subscription->planVersion->features;
```

See [Changelog](./CHANGELOG.md)

## Core Concepts

### Plans vs Plan Versions

- **Plan**: A subscription template (e.g., "Premium Plan")
- **Plan Version**: A specific iteration with pricing and features (e.g., "Premium Plan v2.0")
- **Subscriptions**: Always reference a specific plan version
- **Versioning Benefits**: Update plans without affecting existing subscribers

### Feature Types

- **Consumable**: Trackable usage with limits (e.g., API calls, storage)
- **Non-Consumable**: Boolean access features (e.g., priority support, advanced tools)

### Subscription Lifecycle

1. **Pending**: Created but not yet active (`start_at` is null)
2. **Active**: Currently running subscription
3. **Cancelled**: Marked for cancellation (can be immediate or at period end)
4. **Expired**: Past the end date
5. **Future**: Scheduled to start in the future

## Subscription Management

### Creating Subscriptions

```php
<?php

// Basic subscription
$subscription = $user->subscribe($plan);

// Advanced options
$subscription = $user->subscribe($plan, 
    startAt: now()->addWeek(),     // Future start
    endAt: now()->addYear(),       // Custom end date
    pending: false                 // Active immediately
);

// Pending subscription (for payment processing)
$pendingSubscription = $user->subscribe($plan, pending: true);
```

### Subscription Status

```php
<?php

$subscription = $user->subscriptions()->first();

// Status checks
$subscription->isActive();     // Currently active
$subscription->isPending();    // Awaiting activation
$subscription->isCancelled();  // Marked for cancellation
$subscription->isExpired();    // Past end date
$subscription->isFuture();     // Scheduled to start

// Status transitions (useful for event handling)
$subscription->wasJustActivated();
$subscription->wasJustCancelled();
$subscription->wasJustResumed();
$subscription->wasJustRenewed();
```

### Subscription Operations

```php
<?php

// Cancel subscription
$subscription->cancel();                    // Cancel at period end
$subscription->cancel(immediately: true);   // Cancel immediately

// Resume cancelled subscription
$subscription->resume();
$subscription->resume(startAt: now(), endAt: now()->addMonth());

// Renew subscription
$newSubscription = $subscription->renew();              // From end date
$newSubscription = $subscription->renew(startAt: now()); // From specific date
```

### Querying Subscriptions

```php
<?php

// By status
$user->subscriptions()->active()->get();
$user->subscriptions()->pending()->get();
$user->subscriptions()->cancelled()->get();
$user->subscriptions()->expired()->get();

// By plan
$user->subscriptions()->wherePlan($plan)->get();
$user->subscriptions()->wherePlan('premium')->get(); // Using slug

// By renewal status
$user->subscriptions()->renewed()->get();     // Previously renewed
$user->subscriptions()->notRenewed()->get();  // Not yet renewed
$user->subscriptions()->dueForRenewal()->get(); // Due in 7 days
$user->subscriptions()->dueForRenewal(30)->get(); // Due in 30 days
```

## Feature Management

### Checking Feature Access

```php
<?php

// Basic feature check
$user->hasFeature('priority-support');        // Has the feature
$user->hasActiveFeature('priority-support');  // Has active subscription with feature

// Consumable feature checks
$user->canUseFeature('api-calls', 10);        // Can use 10 units
$user->remainingFeatureUsage('api-calls');    // Remaining usage count

// Next available usage (for reset periods)
$nextReset = $user->nextAvailableFeatureUsage('api-calls');
// Returns Carbon instance, null (unlimited), or false (no reset)
```

### Tracking Feature Usage

```php
<?php

// Record usage
$user->useFeature('api-calls', 5);

// Get usage statistics
$totalUsage = $user->featureUsage('api-calls');
$usageBySubscription = $user->featuresUsage(); // All features

// Through specific subscription
$subscription->useFeature('api-calls', 3);
$subscription->featureUsage('api-calls');
$subscription->remainingFeatureUsage('api-calls');
```

### Feature Configuration

```php
<?php

// Get plan feature details
$planFeature = $subscription->planFeature('api-calls');
echo $planFeature->value;              // Usage limit
echo $planFeature->reset_period;       // Reset frequency
echo $planFeature->reset_period_type;  // Reset period type
echo $planFeature->display_value;      // Human-readable value
echo $planFeature->is_hidden;          // Whether feature is hidden from users
```

### Feature Visibility

Control which features are displayed to end users while keeping them functional for internal logic:

```php
<?php

// Creating hidden features
$plan = PlanBuilder::create('premium')
    ->addFeature('api-calls', fn ($feature) => $feature
        ->value(1000)
        ->displayValue('1,000 API calls')
        // Feature is visible to users by default
    )
    ->addFeature('internal-tracking', fn ($feature) => $feature
        ->value('enabled')
        ->displayValue('Internal tracking')
        ->hidden()  // Hide this feature from user interfaces
    )
    ->addFeature('admin-feature', fn ($feature) => $feature
        ->value('enabled')
        ->hidden(true)  // Explicitly hide
    )
    ->addFeature('visible-feature', fn ($feature) => $feature
        ->value('enabled')
        ->visible()  // Explicitly make visible (default behavior)
    )
    ->build();

// Query visible/hidden features
$visibleFeatures = $planVersion->visibleFeatures;  // Only visible features
$allFeatures = $planVersion->features;             // All features (visible + hidden)

// Using scopes
$visible = PlanFeature::visible()->get();          // All visible plan features
$hidden = PlanFeature::hidden()->get();            // All hidden plan features

// Check visibility
$feature = $planVersion->features->first();
$feature->isVisible();  // true/false
$feature->isHidden();   // true/false
```

**API Behavior:**
- Hidden features remain fully functional for subscription logic and usage tracking
- Only the display/visibility to end users is affected

### Feature Relationships

```php
<?php

use Err0r\Larasub\Models\Feature;

// Get a feature instance
$feature = Feature::slug('api-calls')->first();

// All plan-feature pivot rows for this feature
$planFeatures = $feature->planFeatures;

// All plan versions that include this feature
$planVersions = $feature->planVersions;

// All raw subscription feature usage rows
$usages = $feature->subscriptionFeatureUsages;

// All subscriptions that have used this feature
$subscriptions = $feature->subscriptions;
```

## Plan Versioning

### Creating Plan Versions

```php
<?php

// Create new version of existing plan
$newVersion = PlanBuilder::create('premium') // Same slug
    ->versionLabel('2.0.0')           // Display label
    ->price(129.99, 'USD')            // Updated price
    ->resetPeriod(1, Period::MONTH)
    ->published()
    ->addFeature('api-calls', fn ($feature) => $feature
        ->value(2000)                 // Increased limit
        ->resetPeriod(1, Period::DAY)
    )
    ->build();

// Specify exact version number
$specificVersion = PlanBuilder::create('premium')
    ->versionNumber(5)                // Explicit version
    ->versionLabel('5.0.0-beta')
    ->price(199.99, 'USD')
    ->build();
```

### Working with Versions

```php
<?php

$plan = Plan::slug('premium')->first();

// Get versions
$versions = $plan->versions;                    // All versions
$currentVersion = $plan->currentVersion();      // Latest published & active
$latestVersion = $plan->versions()->latest()->first(); // Latest by number

// Version properties
$version = $plan->versions->first();
$version->version_number;           // e.g., 2
$version->version_label;            // e.g., "2.0.0"
$version->getDisplayVersion();      // Returns label or "v{number}"
$version->isPublished();
$version->isActive();
$version->isFree();

// Version operations
$version->publish();
$version->unpublish();
```

### Subscription Versioning

```php
<?php

// Subscribe to specific version (optional)
$user->subscribe($plan);           // Uses current published version
$user->subscribe($planVersion);    // Uses specific version

// Access version data
$subscription->planVersion->price;           // Version-specific price
$subscription->planVersion->features;        // Version-specific features
$subscription->planVersion->version_number;  // 2
$subscription->planVersion->getDisplayVersion(); // "2.0.0" or "v2"
```

## Events & Lifecycle

The package dispatches events for subscription lifecycle management:

### Available Events

```php
<?php

use Err0r\Larasub\Events\SubscriptionEnded;
use Err0r\Larasub\Events\SubscriptionEndingSoon;

// Triggered when subscription expires
SubscriptionEnded::class

// Triggered when subscription is ending soon (configurable, default: 7 days)
SubscriptionEndingSoon::class
```

### Event Listener Example

```php
<?php

namespace App\Listeners;

use Err0r\Larasub\Events\SubscriptionEnded;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleEndedSubscription implements ShouldQueue
{
    public function handle(SubscriptionEnded $event): void
    {
        $subscription = $event->subscription;
        $user = $subscription->subscriber;
        
        // Send notification, downgrade access, etc.
        $user->notify(new SubscriptionExpiredNotification($subscription));
    }
}
```

### Automatic Event Checking

The package includes an automated scheduler that checks and triggers subscription events every minute. You can enable and configure this scheduler in your `config/larasub.php` file. The scheduler is disabled by default.

## API Resources

Transform your models into JSON responses using the provided resource classes:

```php
<?php

use Err0r\Larasub\Resources\{
    FeatureResource,
    PlanResource,
    PlanVersionResource,
    PlanFeatureResource,
    SubscriptionResource,
    SubscriptionFeatureUsageResource
};

// Transform feature
return FeatureResource::make($feature);

// Transform plan with versions
return PlanResource::make($plan);

// Transform plan version with features
return PlanVersionResource::make($planVersion);

// Transform subscription with plan version
return SubscriptionResource::make($subscription);

// Transform feature usage
return SubscriptionFeatureUsageResource::make($usage);
```

## Configuration

Publish and customize the configuration file:

```bash
php artisan vendor:publish --tag="larasub-config"
```

Key configuration options:

```php
<?php

return [
    // Database table names
    'tables' => [
        'plans' => 'plans',
        'plan_versions' => 'plan_versions',
        'features' => 'features',
        'subscriptions' => 'subscriptions',
        // ...
    ],
    
    // Event scheduling
    'schedule' => [
        'check_ending_subscriptions' => '* * * * *', // Every minute
    ],
    
    // Notification settings
    'subscription_ending_soon_days' => 7,
    
    // Model configurations
    'models' => [
        'plan' => \Err0r\Larasub\Models\Plan::class,
        'subscription' => \Err0r\Larasub\Models\Subscription::class,
        // ...
    ],
];
```

## Commands

The package provides several Artisan commands:

### Migration Command
```bash
# Migrate from v2.x to v3.x with plan versioning
php artisan larasub:migrate-to-versioning

# Dry run to preview changes
php artisan larasub:migrate-to-versioning --dry-run

# Force without confirmation
php artisan larasub:migrate-to-versioning --force
```

### Subscription Monitoring
```bash
# Check for ending subscriptions (usually run via scheduler)
php artisan larasub:check-ending-subscriptions
```

### Development Tools
```bash
# Seed sample data for development
php artisan larasub:seed
```

## Testing

Run the test suite:

```bash
composer test

# With coverage
composer test-coverage

# Code analysis
composer analyse

# Code formatting
composer format
```

<!---
## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details on:

- Setting up the development environment
- Running tests
- Code style guidelines
- Submitting pull requests

## Security

If you discover any security vulnerabilities, please review our [Security Policy](../../security/policy) for responsible disclosure procedures.

## Credits

- **Author**: [Faisal](https://github.com/err0r)
- **Contributors**: [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

**Star this repository** to stay updated on new features and releases!
-->
