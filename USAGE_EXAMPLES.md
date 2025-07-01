# 🚀 Extra Credits Feature - Usage Examples

This document provides comprehensive examples of how to use the new **Extra Credits** feature in the Larasub package.

## 📋 Table of Contents

1. [Basic Usage](#basic-usage)
2. [Referral System Implementation](#referral-system-implementation)
3. [Promotional Campaigns](#promotional-campaigns)
4. [Credit Management](#credit-management)
5. [Advanced Queries](#advanced-queries)
6. [API Integration](#api-integration)

## 🎯 Basic Usage

### Granting Extra Credits

```php
// Grant 5 extra API calls to a user
$user->grantExtraCredits('api-calls', 5, 'Welcome bonus');

// Grant credits with expiration
$user->grantExtraCredits('api-calls', 10, 'Promotional offer', [
    'expires_at' => now()->addMonths(3),
    'granted_by' => auth()->user()
]);

// Grant credits directly to a subscription
$subscription = $user->activeSubscription();
$subscription->grantExtraCredits('storage-gb', 2.5, [
    'reason' => 'Beta testing reward',
    'granted_by' => $admin,
    'expires_at' => now()->addDays(30)
]);
```

### Checking Available Credits

```php
// Check total available credits (plan + extra)
$totalCredits = $user->totalAvailableCredits('api-calls');

// Check only extra credits
$extraCredits = $user->extraCredits('api-calls');

// Get detailed statistics
$stats = $user->getCreditUsageStats('api-calls');
/*
Returns:
[
    'plan_limit' => 100,
    'extra_credits' => 25,
    'total_available' => 125,
    'used' => 30,
    'remaining' => 95
]
*/
```

### Using Features with Credits

```php
// The existing methods automatically include extra credits
if ($user->canUseFeature('api-calls', 10)) {
    $user->useFeature('api-calls', 10); // Consumes extra credits first
}

// Or explicitly control credit usage
if ($user->canUseFeature('api-calls', 10, true)) { // include extra credits
    $user->useFeature('api-calls', 10, true); // use extra credits
}
```

## 🤝 Referral System Implementation

### Step 1: Create Referral Logic

```php
class ReferralService
{
    public function processReferral(User $referrer, User $newUser): void
    {
        // Grant bonus credits to the referrer
        $referrer->grantExtraCredits('api-calls', 5, 'Referral bonus', [
            'granted_by' => $newUser,
            'expires_at' => now()->addMonths(6)
        ]);

        // Grant welcome credits to the new user
        $newUser->grantExtraCredits('api-calls', 3, 'Welcome bonus', [
            'granted_by' => $referrer,
            'expires_at' => now()->addMonth()
        ]);

        // Log the event
        Log::info("Referral processed", [
            'referrer_id' => $referrer->id,
            'new_user_id' => $newUser->id,
            'credits_granted' => 8
        ]);
    }
}
```

### Step 2: Handle User Registration

```php
class UserController extends Controller
{
    public function register(Request $request, ReferralService $referralService)
    {
        $user = User::create($request->validated());
        
        // Subscribe user to a plan
        $plan = Plan::slug('basic')->first();
        $user->subscribe($plan);

        // Process referral if referral code exists
        if ($request->has('referral_code')) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
            
            if ($referrer) {
                $referralService->processReferral($referrer, $user);
            }
        }

        return response()->json(['user' => $user]);
    }
}
```

## 🎉 Promotional Campaigns

### Flash Sale Credits

```php
class PromotionalCampaignService
{
    public function runFlashSale(): void
    {
        // Grant credits to all active subscribers
        $activeSubscriptions = Subscription::active()->get();
        
        foreach ($activeSubscriptions as $subscription) {
            $subscription->grantExtraCredits('api-calls', 50, [
                'reason' => 'Flash Sale - 50% more credits!',
                'expires_at' => now()->addDays(7),
                'granted_by' => auth()->user()
            ]);
        }
    }

    public function loyaltyReward(User $user): void
    {
        $monthsSubscribed = $user->subscriptions()
            ->where('created_at', '<=', now()->subMonths(12))
            ->count();

        if ($monthsSubscribed >= 12) {
            $bonusCredits = $monthsSubscribed * 10; // 10 credits per month
            
            $user->grantExtraCredits('api-calls', $bonusCredits, 'Loyalty reward - 1 year subscriber');
        }
    }
}
```

### Seasonal Promotions

```php
class SeasonalPromotions
{
    public function christmasBonus(): void
    {
        User::whereHas('subscriptions', function ($query) {
            $query->active();
        })->chunk(100, function ($users) {
            foreach ($users as $user) {
                $user->grantMultipleExtraCredits([
                    'api-calls' => 100,
                    'storage-gb' => 5,
                    'exports' => 20
                ], 'Christmas bonus! 🎄', [
                    'expires_at' => now()->addDays(31)
                ]);
            }
        });
    }
}
```

## 🔧 Credit Management

### Administrative Tools

```php
class CreditManagementController extends Controller
{
    public function grantCredits(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'feature_slug' => 'required|string',
            'credits' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now'
        ]);

        $user = User::findOrFail($request->user_id);
        
        $credit = $user->grantExtraCredits(
            $request->feature_slug,
            $request->credits,
            $request->reason,
            [
                'expires_at' => $request->expires_at,
                'granted_by' => auth()->user()
            ]
        );

        return response()->json([
            'message' => 'Credits granted successfully',
            'credit' => new SubscriptionFeatureCreditResource($credit)
        ]);
    }

    public function getCreditHistory(User $user, string $featureSlug)
    {
        $credits = $user->activeSubscription()
            ->extraCreditsForFeature($featureSlug)
            ->with(['feature', 'grantedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return SubscriptionFeatureCreditResource::collection($credits);
    }
}
```

### Automated Credit Cleanup

```php
// In your scheduler (app/Console/Kernel.php)
protected function schedule(Schedule $schedule)
{
    // Clean up expired credits daily
    $schedule->command('larasub:cleanup-expired-credits')
        ->daily()
        ->at('02:00');
    
    // Send expiration warnings
    $schedule->call(function () {
        $expiringCredits = SubscriptionFeatureCredit::active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(7))
            ->with(['subscription.subscriber', 'feature'])
            ->get();

        foreach ($expiringCredits as $credit) {
            // Send notification to user
            $credit->subscription->subscriber->notify(
                new CreditsExpiringNotification($credit)
            );
        }
    })->weekly();
}
```

## 🔍 Advanced Queries

### Analytics and Reporting

```php
class CreditAnalytics
{
    public function getCreditUsageReport(string $period = 'month'): array
    {
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };

        return [
            'total_credits_granted' => SubscriptionFeatureCredit::where('created_at', '>=', $startDate)
                ->sum('credits'),
            
            'active_credits' => SubscriptionFeatureCredit::active()
                ->sum('credits'),
            
            'expired_credits' => SubscriptionFeatureCredit::expired()
                ->where('created_at', '>=', $startDate)
                ->sum('credits'),
            
            'credits_by_feature' => SubscriptionFeatureCredit::where('created_at', '>=', $startDate)
                ->with('feature')
                ->get()
                ->groupBy('feature.slug')
                ->map(fn ($credits) => $credits->sum('credits')),
            
            'credits_by_reason' => SubscriptionFeatureCredit::where('created_at', '>=', $startDate)
                ->whereNotNull('reason')
                ->get()
                ->groupBy('reason')
                ->map(fn ($credits) => $credits->sum('credits'))
        ];
    }

    public function getTopCreditRecipients(int $limit = 10): Collection
    {
        return User::whereHas('subscriptions.extraCredits')
            ->withSum('subscriptions.extraCredits as total_extra_credits', 'credits')
            ->orderBy('total_extra_credits', 'desc')
            ->limit($limit)
            ->get();
    }
}
```

### Complex Queries

```php
// Find users with expiring credits
$usersWithExpiringCredits = User::whereHas('subscriptions.extraCredits', function ($query) {
    $query->active()
        ->where('expires_at', '<=', now()->addDays(7));
})->with(['subscriptions.extraCredits' => function ($query) {
    $query->active()
        ->where('expires_at', '<=', now()->addDays(7))
        ->with('feature');
}])->get();

// Get subscription with highest extra credits for a feature
$topSubscription = Subscription::active()
    ->withSum(['extraCredits as total_api_credits' => function ($query) {
        $query->forFeature('api-calls')->active();
    }], 'credits')
    ->orderBy('total_api_credits', 'desc')
    ->first();

// Find credits granted by specific admin
$adminGrantedCredits = SubscriptionFeatureCredit::where('granted_by_type', User::class)
    ->where('granted_by_id', $admin->id)
    ->with(['subscription.subscriber', 'feature'])
    ->get();
```

## 🌐 API Integration

### RESTful Endpoints

```php
// routes/api.php
Route::middleware('auth:api')->group(function () {
    // Credit management
    Route::post('/users/{user}/credits', [CreditController::class, 'grant']);
    Route::get('/users/{user}/credits', [CreditController::class, 'index']);
    Route::get('/users/{user}/credits/{feature}', [CreditController::class, 'show']);
    
    // Usage statistics
    Route::get('/users/{user}/usage-stats/{feature}', [UsageController::class, 'stats']);
    
    // Admin endpoints
    Route::middleware('admin')->group(function () {
        Route::get('/admin/credits/analytics', [AdminController::class, 'creditAnalytics']);
        Route::post('/admin/credits/bulk-grant', [AdminController::class, 'bulkGrantCredits']);
        Route::delete('/admin/credits/expired', [AdminController::class, 'cleanupExpired']);
    });
});
```

### API Response Examples

```json
// GET /api/users/123/credits/api-calls
{
    "data": {
        "feature": "api-calls",
        "plan_limit": 100,
        "extra_credits": 25,
        "total_available": 125,
        "used": 30,
        "remaining": 95,
        "credits": [
            {
                "id": "uuid-here",
                "credits": 15,
                "reason": "Referral bonus",
                "expires_at": "2024-06-01T00:00:00Z",
                "is_active": true,
                "days_until_expiration": 45
            },
            {
                "id": "uuid-here-2",
                "credits": 10,
                "reason": "Promotional offer",
                "expires_at": null,
                "is_active": true,
                "days_until_expiration": null
            }
        ]
    }
}
```

## 🎯 Best Practices

1. **Always set expiration dates** for promotional credits to prevent indefinite accumulation
2. **Track who grants credits** using the `granted_by` relationship for audit trails
3. **Use descriptive reasons** to help with support and analytics
4. **Monitor credit usage patterns** to detect abuse or unexpected behavior
5. **Set up automated cleanup** of expired credits to maintain database performance
6. **Implement rate limiting** on credit granting to prevent abuse
7. **Use transactions** when granting multiple credits to ensure consistency

## 🔒 Security Considerations

```php
// Always validate credit granting permissions
class GrantCreditsRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('grant-credits');
    }

    public function rules()
    {
        return [
            'credits' => 'required|numeric|min:0.01|max:10000', // Prevent excessive grants
            'feature_slug' => 'required|exists:features,slug',
            'reason' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now|before:' . now()->addYear()
        ];
    }
}
```

This comprehensive implementation provides a robust, scalable solution for managing extra credits in subscription-based applications while maintaining backward compatibility with existing Larasub functionality.