# Larasub v4 - Simple Laravel Subscriptions

[![Latest Version on Packagist](https://img.shields.io/packagist/v/err0r/larasub.svg?style=flat-square)](https://packagist.org/packages/err0r/larasub)
[![Total Downloads](https://img.shields.io/packagist/dt/err0r/larasub.svg?style=flat-square)](https://packagist.org/packages/err0r/larasub)

A dead-simple subscription management package for Laravel. No overwhelming features, just the essentials.

## Why Larasub v4?

- **Simple**: Just plans and subscriptions. That's it.
- **Lightweight**: Only 2 database tables in the core
- **Flexible**: Use only what you need
- **Laravel-friendly**: Follows Laravel conventions
- **No Learning Curve**: If you know Laravel, you know Larasub

## Installation

```bash
composer require err0r/larasub
```

Publish the config and migrations:

```bash
php artisan vendor:publish --tag="larasub-config"
php artisan vendor:publish --tag="larasub-migrations"
php artisan migrate
```

## Quick Start

### 1. Add the trait to your User model

```php
use Err0r\Larasub\Core\Traits\HasSubscriptions;

class User extends Authenticatable
{
    use HasSubscriptions;
}
```

### 2. Create a plan

```php
use Err0r\Larasub\Core\Models\Plan;

// Using the model
$plan = Plan::create([
    'name' => 'Premium',
    'slug' => 'premium',
    'price' => 9.99,
    'currency' => 'USD',
    'period' => 'month',
    'period_count' => 1,
]);

// Or using the command
php artisan larasub:create-plan "Premium" 9.99 --period=month
```

### 3. Subscribe a user

```php
// Simple subscription
$subscription = $user->subscribe($plan);

// With options
$subscription = $user->subscribe($plan, [
    'trial_ends_at' => now()->addDays(7),
]);

// Start in the future
$subscription = $user->subscribeFrom(now()->addWeek(), $plan);
```

### 4. Check subscription status

```php
// Check if user has any active subscription
if ($user->hasSubscription()) {
    // User is subscribed
}

// Check for specific plan
if ($user->subscribedTo('premium')) {
    // User has premium plan
}

// Get the active subscription
$subscription = $user->subscription();

// Check subscription details
$subscription->isActive();      // Currently active?
$subscription->onTrial();       // On trial period?
$subscription->isCancelled();   // Cancelled?
$subscription->endingSoon(7);   // Ending in 7 days?
```

## Core Features

### Plans

Plans are simple and straightforward:

```php
use Err0r\Larasub\Core\Models\Plan;

// Create plans
$basic = Plan::create([
    'name' => 'Basic',
    'price' => 4.99,
    'period' => 'month',
]);

$premium = Plan::create([
    'name' => 'Premium',
    'price' => 99.99,
    'period' => 'year',
]);

// Query plans
$activePlans = Plan::active()->get();
$plan = Plan::slug('premium')->first();

// Plan helpers
$plan->isFree();                    // Is it free?
$plan->calculateEndDate();          // When will subscription end?
$plan->calculateEndDate($startDate); // From specific date
```

### Subscriptions

Manage subscriptions with ease:

```php
// Subscribe
$subscription = $user->subscribe($plan);

// With trial
$subscription = $user->subscribeWithTrial($plan, 14); // 14 days trial

// Cancel
$subscription->cancel();              // Cancel at period end
$subscription->cancel(true);          // Cancel immediately

// Resume cancelled subscription
$subscription->resume();

// Renew subscription
$subscription->renew();

// Extend subscription
$subscription->extend(30);            // Add 30 days

// Switch plans
$user->switchTo($newPlan);           // Switch at period end
$user->switchToNow($newPlan);        // Switch immediately
```

### Subscription Queries

```php
// User's subscriptions
$user->subscriptions()->get();        // All subscriptions
$user->activeSubscriptions()->get();  // Active only
$user->subscription();                // Current active subscription

// Query scopes
Subscription::active()->get();        // All active subscriptions
Subscription::cancelled()->get();     // Cancelled subscriptions
Subscription::expired()->get();       // Expired subscriptions
Subscription::onTrial()->get();       // On trial
```

## Configuration

The package comes with sensible defaults. Customize as needed:

```php
// config/larasub.php
return [
    // Use UUIDs instead of auto-increment IDs
    'use_uuid' => false,
    
    // Table names
    'tables' => [
        'plans' => 'plans',
        'subscriptions' => 'subscriptions',
    ],
    
    // Default currency for plans
    'default_currency' => 'USD',
    
    // Default subscription settings
    'subscription_defaults' => [
        'trial_days' => 0,
        'auto_renew' => true,
    ],
];
```

## Database Structure

Only 2 tables in the core:

### Plans Table
- `id`
- `name`
- `slug` (unique)
- `description`
- `price`
- `currency`
- `period` (day/week/month/year)
- `period_count`
- `metadata` (JSON)
- `is_active`
- `sort_order`
- `timestamps`
- `deleted_at`

### Subscriptions Table
- `id`
- `plan_id`
- `subscriber_type` / `subscriber_id` (polymorphic)
- `status` (pending/active/cancelled/expired)
- `starts_at`
- `ends_at`
- `cancelled_at`
- `trial_ends_at`
- `metadata` (JSON)
- `timestamps`
- `deleted_at`

## Optional Features Module

Need feature-based subscriptions? Enable the optional features module:

```php
// config/larasub.php
'features' => [
    'enabled' => true,
],
```

Then publish and run the features migrations:

```bash
php artisan vendor:publish --tag="larasub-features-migrations"
php artisan migrate
```

This adds:
- Feature management
- Usage tracking
- Limits and quotas

See [Features Documentation](docs/features.md) for details.

## Real-World Examples

### SaaS Application

```php
// Create your plans
$starter = Plan::create([
    'name' => 'Starter',
    'price' => 9,
    'period' => 'month',
]);

$pro = Plan::create([
    'name' => 'Professional', 
    'price' => 29,
    'period' => 'month',
]);

// In your controller
public function subscribe(Request $request)
{
    $plan = Plan::findOrFail($request->plan_id);
    
    // Create subscription with trial
    $subscription = auth()->user()->subscribeWithTrial($plan, 14);
    
    // Process payment with your provider
    // ...
    
    // Activate after payment
    $subscription->activate();
    
    return redirect()->route('dashboard');
}

// In your middleware
public function handle($request, $next)
{
    if (!$request->user()->hasSubscription()) {
        return redirect()->route('pricing');
    }
    
    return $next($request);
}

// In your views
@if(auth()->user()->subscribedTo('professional'))
    <button>Access Pro Features</button>
@endif
```

### Subscription Management Page

```php
public function index()
{
    $user = auth()->user();
    
    return view('subscriptions.index', [
        'subscription' => $user->subscription(),
        'plans' => Plan::active()->get(),
    ]);
}

public function cancel()
{
    auth()->user()->subscription()->cancel();
    
    return back()->with('message', 'Subscription cancelled.');
}

public function resume()
{
    auth()->user()->subscription()->resume();
    
    return back()->with('message', 'Subscription resumed.');
}
```

## Migrating from v3

The new v4 is a complete rewrite focusing on simplicity:

1. **No more plan versioning** - Just create new plans when needed
2. **No more complex builders** - Use standard Laravel model creation
3. **Simplified relationships** - Plans → Subscriptions (that's it!)
4. **Optional features** - Enable only if needed

See [Migration Guide](docs/migration-v3-to-v4.md) for detailed steps.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). See [License File](LICENSE.md) for more information.
