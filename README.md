# Laravel Subscription Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/err0r/larasub.svg?style=flat-square)](https://packagist.org/packages/err0r/larasub)
[![Total Downloads](https://img.shields.io/packagist/dt/err0r/larasub.svg?style=flat-square)](https://packagist.org/packages/err0r/larasub)

> [!IMPORTANT]  
> This package is currently under development and is **not yet ready for production use**.  
> Click the **Watch** button to stay updated and be notified when the package is ready for deployment!

A powerful and flexible subscription management system for Laravel applications that provides:

✨ **Core Features**
- 📦 Subscription Plans with tiered pricing
- 🔄 Flexible billing periods (minute/hour/day/week/month/year)
- 🎯 Feature-based access control
- 📊 Usage tracking and limits
- 🔋 Consumable and non-consumable features

⚡ **Key Capabilities**
- 💳 Subscribe users to plans with custom periods
- 📈 Track feature usage and quotas
- ⏰ Built-in subscription lifecycle events
- 🔄 Cancel/Resume subscription workflows
- 📅 Period-based feature resets
- 🌐 Multi-language support (translatable plans/features)
- 🔍 Feature usage monitoring
- 🎚️ Customizable usage limits

🛠️ **Developer Experience**
- 🧩 Simple trait-based integration
- ⚙️ Configurable tables and models
- 📝 Comprehensive event system
- 🔌 UUID support out of the box

## Table of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Advanced Usage](#advanced-usage)
- [Configuration](#configuration)
- [Resource Classes](#resource-classes)
- [Testing](#testing)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

## Installation

You can install the package via composer:

```bash
composer require err0r/larasub
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="larasub-config"
```

Publish and run the migrations with:

```bash
php artisan vendor:publish --tag="larasub-migrations"
php artisan migrate
```

## Basic Usage

- **Setup the Subscriber Model**  
   Add the `HasSubscription` trait to your model:

    ```php
    <?php
    use Err0r\Larasub\Traits\HasSubscription;

    class User extends Model
    {
        use HasSubscription;
    }
    ```

- **Create a Feature**

    ```php
    <?php
    use Err0r\Larasub\Builders\FeatureBuilder;

    // Create a new feature
    $apiCalls = FeatureBuilder::create('api-calls')
        ->name(['en' => 'API Calls', 'ar' => 'مكالمات API'])
        ->description(['en' => 'Number of API calls allowed', 'ar' => 'عدد المكالمات المسموح به'])
        ->consumable()
        ->sortOrder(1)
        ->build();

    $apiCallPriority = FeatureBuilder::create('api-call-priority')
        ->name(['en' => 'API Calls Priority', 'ar' => 'الاولوية في مكالمات الـ API'])
        ->description(['en' => 'Priority access to API calls', 'ar' => 'الوصول بأولوية إلى مكالمات الـ API'])
        ->nonConsumable()
        ->sortOrder(2)
        ->build();
    ```

- **Create a Plan**
    
    Create subscription plans using the `PlanBuilder` class. When configuring a plan's features, you can specify:

    - Feature values and display names
    - Consumption mode (consumable vs non-consumable)
    - Reset intervals (periodic vs fixed)
    - Additional feature properties

    ```php
    <?php
    use Err0r\Larasub\Builders\PlanBuilder;
    use Err0r\Larasub\Enums\Period;
    use Err0r\Larasub\Enums\FeatureValue;

    // Create a new plan
    $plan = PlanBuilder::create('premium')
        ->name(['en' => 'Premium Plan', 'ar' => 'خطة مميزة'])
        ->description(['en' => 'Access to premium features', 'ar' => 'الوصول إلى الميزات المميزة'])
        ->price(99.99, 'USD')
        ->resetPeriod(1, Period::MONTH)
        ->addFeature('api-calls', fn ($feature) => $feature
            ->value(1000)
            ->resetPeriod(1, Period::DAY)
            ->displayValue(['en' => '1000 API Calls', 'ar' => '1000 مكالمة API'])
            ->sortOrder(1);
        )
        ->addFeature('download-requests', fn ($feature) => $feature
            ->value(FeatureValue::UNLIMITED)
            ->displayValue(['en' => 'Unlimited Download Requests', 'ar' => 'طلبات تنزيل غير محدودة'])
            ->sortOrder(2);
        )
        ->addFeature('api-call-priority', fn ($feature) => $feature
            ->value('high')
            ->displayValue(['en' => 'High Priority API Calls', 'ar' => 'مكالمات API ذات أولوية عالية'])
            ->sortOrder(3);
        )
        ->build();
    ```

- **Create a Subscription**

    ```php
    <?php
    // Get a plan
    $plan = Plan::slug('basic')->first();

    // Subscribe user to the plan
    $user->subscribe($plan);

    // Subscribe user to a plan but make it pending (useful when processing payments)
    // *Pending subscriptions has start_at set to null
    $user->subscribe($plan, pending: true);

    // Subscribe with custom dates
    $user->subscribe($plan, startAt: now(),  endAt: now()->addYear());
    ```

- **Check Subscription Status**

    ```php
    <?php
    $subscription = $user->subscriptions()->first();

    // Check if subscription is active
    $subscription->isActive();

    // Check if subscription is pending
    $subscription->isPending();

    // Check if subscription is cancelled
    $subscription->isCancelled();

    // Check if subscription has expired
    $subscription->isExpired();
    ```

- **Feature Management**

    ```php
    <?php
    // Check if user has a feature
    $user->hasFeature('unlimited-storage');

    // Check if user can use a feature
    $user->canUseFeature('api-calls', 1);

    // Track feature usage
    $user->useFeature('api-calls', 1);

    // Check remaining feature usage
    $user->remainingFeatureUsage('api-calls');
    ```

## Advanced Usage

- **Subscription Management**

    ```php
    <?php
    // Get all active subscriptions
    $user->subscriptions()->active()->get();

    // Cancel a subscription (ends subscription at the end of the billing period)
    $subscription->cancel();

    // Cancel immediately (ends subscription now)
    $subscription->cancel(immediately: true);

    // Resume subscription (if cancelled or pending)
    $subscription->resume(
        startAt: now(), // Optional. Default: Now
        endAt: now()->addMonth() // Optional. Default: Subscription Start Date + Plan Duration
    );
    ```

- **Subscription Renewal**

    ```php
    // Check renewal status
    $subscription->isRenewed();    // Has been renewed
    $subscription->isRenewal();    // Is a renewal of another subscription

    // Renew a subscription
    $newSubscription = $subscription->renew();              // Renews from end_at
    $newSubscription = $subscription->renew(startAt: now()); // Renews from specific date

    // Query renewals
    $user->subscriptions()->renewed()->get();     // Get all renewed subscriptions
    $user->subscriptions()->notRenewed()->get();  // Get subscriptions not renewed
    
    // Find subscriptions due for renewal in next 7 days
    $user->subscriptions()->dueForRenewal()->get();
    
    // Find subscriptions due for renewal in next 30 days
    $user->subscriptions()->dueForRenewal(30)->get();
    ```

- **Subscription Status Checks**

    ```php
    <?php
    $subscription = $user->subscriptions()->first();

    // Check subscription status
    $subscription->isActive();    // Not expired, future, pending or cancelled
    $subscription->isPending();   // Start date is null 
    $subscription->isCancelled(); // Has cancellation date
    $subscription->isExpired();   // End date has passed
    $subscription->isFuture();    // Start date is in the future

    // Query subscriptions by status
    $user->subscriptions()->active()->get();
    $user->subscriptions()->pending()->get();
    $user->subscriptions()->cancelled()->get();
    $user->subscriptions()->expired()->get();
    $user->subscriptions()->future()->get();

    // Plan-specific queries
    $user->subscriptions()->wherePlan($plan)->get();
    $user->subscriptions()->wherePlan('premium')->get(); // Using plan slug
    $user->subscriptions()->whereNotPlan($plan)->get();
    ```

- **Status Transition Detection**

    ```php
    <?php
    $subscription = $user->subscriptions()->first();

    // Check if any status changed
    $subscription->hasStatusTransitioned();

    // Check specific status transitions
    $subscription->wasJustActivated();    // null -> date for start_at
    $subscription->wasJustCancelled();    // null -> date for cancelled_at
    $subscription->wasJustResumed();      // date -> null for cancelled_at
    $subscription->wasJustRenewed();      // null -> id for renewed_from_id
    ```

    These methods help detect when a subscription's status has just changed:
    - `hasStatusTransitioned()`: Checks if any status transition occurred
    - `wasJustActivated()`: Detects activation (start date set)
    - `wasJustCancelled()`: Detects cancellation (cancel date set)
    - `wasJustResumed()`: Detects resumption (cancel date cleared)
    - `wasJustRenewed()`: Detects renewal (renewal ID set)

- **Feature Management**   

    ```php
    <?php
    $feature = Feature::slug('api-calls')->first();
    $feature->isConsumable();
    $feature->isNonConsumable();

    // Through subscriber's active subscriptions
    $user->featuresUsage();
    $user->featureUsage('api-calls');
    $user->planFeature('premium-support');
    $user->usableFeature('api-calls', 1); // returns collection of PlanFeature that can be used given the passed value
    $user->hasActiveFeature('unlimited-storage');
    $user->canUseFeature('api-calls', 1);
    $user->useFeature('api-calls', 1);
    $user->remainingFeatureUsage('api-calls');
    $user->nextAvailableFeatureUsage('api-calls'); // `Carbon` instance of next available usage, `null` if unlimited, or `false` if feature is not resetable

    // Through specific subscription
    $subscription->featuresUsage()->get();
    $subscription->featureUsage('api-calls')->get();
    $subscription->planFeature('premium-support');
    $subscription->hasFeature('premium-support'); // Return true if subscription has the feature (regardless of subscription status)
    $subscription->hasActiveFeature('premium-support'); // Return true if subscription is active and has the feature
    $subscription->remainingFeatureUsage('api-calls');
    $subscription->nextAvailableFeatureUsage('api-calls');
    $subscription->canUseFeature('api-calls', 1);
    $subscription->useFeature('api-calls', 1);
    ```

- **Events**

    The package dispatches events for subscription lifecycle:
    - `SubscriptionEnded` - When a subscription expires
    - `SubscriptionEndingSoon` - When a subscription is ending soon (configurable in `larasub.php`. Default: 7 days)

    > By default, the package includes a task schedule that runs every minute to check for subscriptions that have ended or are ending soon, and triggers the corresponding events.   
    > You can modify this schedule in the `larasub.php` configuration file.

    **Event Listener Example:**
    ```php
    <?php

    namespace App\Listeners;

    use Err0r\Larasub\Events\SubscriptionEnded;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Support\Facades\Log;

    class HandleEndedSubscription
    {
        /**
         * Handle the event.
         */
        public function handle(SubscriptionEnded $event): void
        {
            // Handle subscription ending
        }
    }
    ```

## Resource Classes

The package provides several resource classes to transform your models into JSON representations:

- [`FeatureResource`](src/Resources/FeatureResource.php): Transforms a feature model.
- [`PlanResource`](src/Resources/PlanResource.php): Transforms a plan model.
- [`PlanFeatureResource`](src/Resources/PlanFeatureResource.php): Transforms a plan feature model.
- [`SubscriptionResource`](src/Resources/SubscriptionResource.php): Transforms a subscription model.
- [`SubscriptionFeatureUsageResource`](src/Resources/SubscriptionFeatureUsageResource.php): Transforms a subscription feature usage model.

## Testing
> TODO   

```bash
composer test
```

## Changelog
> TODO   

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing
> TODO   

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities
> TODO   

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Faisal](https://github.com/err0r)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.