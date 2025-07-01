# 🧠 Extra Custom Credits Feature Design for Larasub Package

## 📋 Overview

This document outlines the design and implementation strategy for adding **extra custom credits** functionality to the Larasub Laravel package. This feature allows users to receive additional credits for specific features beyond what their subscribed plan provides.

## 🎯 Feature Requirements

- Allow manual or programmatic granting of extra credits to users per feature
- Combine plan's feature limit with user-specific extra credits when checking usage
- Non-breaking for existing implementations
- Follow Laravel best practices
- Integrate smoothly with existing `Plan`, `Feature`, and `Subscription` models

## 🏗️ Database Schema Design

### New Table: `subscription_feature_credits`

```sql
CREATE TABLE subscription_feature_credits (
    id UUID PRIMARY KEY,
    subscription_id UUID NOT NULL,
    feature_id UUID NOT NULL,
    credits DECIMAL(15,4) NOT NULL DEFAULT 0,
    reason VARCHAR(255) NULL,
    granted_by_type VARCHAR(255) NULL,
    granted_by_id UUID NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE,
    INDEX idx_subscription_feature (subscription_id, feature_id),
    INDEX idx_expires_at (expires_at)
);
```

**Key Design Decisions:**
- **Per-subscription credits**: Credits are tied to specific subscriptions, not users directly
- **Decimal precision**: Supports fractional credits (e.g., 2.5 API calls)
- **Audit trail**: Tracks who granted the credits and why
- **Expiration support**: Credits can have expiration dates
- **Polymorphic granter**: Supports different types of entities granting credits

## 📊 Model Architecture

### New Model: `SubscriptionFeatureCredit`

```php
<?php

namespace Err0r\Larasub\Models;

use Carbon\Carbon;
use Err0r\Larasub\Traits\HasConfigurableIds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SubscriptionFeatureCredit extends Model
{
    use HasConfigurableIds;
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'feature_id',
        'credits',
        'reason',
        'granted_by_type',
        'granted_by_id',
        'expires_at',
    ];

    protected $casts = [
        'credits' => 'decimal:4',
        'expires_at' => 'datetime',
    ];

    // Relationships and scopes...
}
```

## 🔧 Implementation Strategy

### Phase 1: Database and Model Setup

1. **Migration**: Create the new table
2. **Model**: Implement `SubscriptionFeatureCredit` model
3. **Relationships**: Add relationships to existing models
4. **Configuration**: Update config file

### Phase 2: Core Functionality

1. **Credit Management**: Methods to grant, revoke, and query credits
2. **Usage Calculation**: Modify existing usage methods to include extra credits
3. **Expiration Handling**: Automatic cleanup of expired credits

### Phase 3: API and Integration

1. **Trait Methods**: Add methods to `HasSubscription` trait
2. **Builder Methods**: Add query scopes and builders
3. **Events**: Fire events when credits are granted/used

## 🚀 Usage Examples

### Granting Extra Credits

```php
// Grant 5 extra credits for API calls to a user's subscription
$user->activeSubscription()->grantExtraCredits('api-calls', 5, [
    'reason' => 'Referral bonus',
    'granted_by' => auth()->user(),
    'expires_at' => now()->addMonths(3)
]);

// Or using the user trait method
$user->grantExtraCredits('api-calls', 5, 'Referral bonus');
```

### Checking Available Credits

```php
// Check total available credits (plan + extra)
$totalCredits = $user->totalAvailableCredits('api-calls');

// Check only extra credits
$extraCredits = $user->extraCredits('api-calls');

// Check if user can use feature (includes extra credits)
if ($user->canUseFeature('api-calls', 10)) {
    $user->useFeature('api-calls', 10);
}
```

### Querying Credit History

```php
// Get all extra credits for a feature
$credits = $user->activeSubscription()->extraCredits('api-calls');

// Get credits by reason
$referralCredits = $user->activeSubscription()
    ->extraCredits('api-calls')
    ->where('reason', 'like', '%referral%');
```

## 🔄 Integration Points

### Modified Methods

The following existing methods will be enhanced to include extra credits:

1. **`remainingFeatureUsage()`**: Include extra credits in calculation
2. **`canUseFeature()`**: Consider extra credits when checking availability
3. **`useFeature()`**: Deduct from extra credits first, then plan credits

### Backward Compatibility

- All existing API methods remain unchanged
- Extra credits are additive - they don't replace existing functionality
- If no extra credits exist, behavior is identical to current implementation

## 📈 Advanced Features

### Credit Prioritization

Credits are consumed in this order:
1. **Extra credits** (oldest first, respecting expiration)
2. **Plan credits** (standard plan allowance)

### Bulk Operations

```php
// Grant credits to multiple users
$users->each(fn($user) => $user->grantExtraCredits('api-calls', 5, 'Promotion'));

// Grant multiple features at once
$user->grantMultipleExtraCredits([
    'api-calls' => 100,
    'storage-gb' => 5,
    'exports' => 10
], 'Premium upgrade bonus');
```

### Reporting and Analytics

```php
// Get credit usage statistics
$stats = $user->getCreditUsageStats('api-calls');
// Returns: ['plan_used' => 50, 'extra_used' => 15, 'remaining_plan' => 50, 'remaining_extra' => 10]

// Get credits granted in a period
$monthlyCredits = SubscriptionFeatureCredit::grantedInPeriod(
    now()->startOfMonth(),
    now()->endOfMonth()
);
```

## 🔒 Security Considerations

1. **Authorization**: Only authorized users can grant credits
2. **Validation**: Prevent negative credits or excessive amounts
3. **Audit Trail**: Complete tracking of who granted what and when
4. **Rate Limiting**: Prevent abuse of credit granting

## 🧪 Testing Strategy

1. **Unit Tests**: Test all new model methods and relationships
2. **Integration Tests**: Test interaction with existing subscription logic
3. **Feature Tests**: Test complete workflows (grant → use → expire)
4. **Performance Tests**: Ensure credit calculations don't impact performance

## 📝 Documentation Updates

1. **README**: Add usage examples and feature overview
2. **API Documentation**: Document all new methods and parameters
3. **Migration Guide**: Help existing users adopt the feature
4. **Examples**: Provide real-world use cases

## 🎉 Benefits

1. **Flexibility**: Supports various business models (referrals, promotions, loyalty)
2. **Granular Control**: Per-feature, per-user credit management
3. **Audit Trail**: Complete visibility into credit grants and usage
4. **Performance**: Efficient queries with proper indexing
5. **Extensibility**: Easy to add new credit types or rules

This design provides a robust, scalable solution for adding extra custom credits to the Larasub package while maintaining backward compatibility and following Laravel best practices.