# Larasub v4.0 - Simplification Plan

## Overview
The current package is overwhelming due to excessive complexity. This plan outlines a complete redesign focused on simplicity and ease of use.

## Core Problems with Current Design
1. **Plan Versioning**: Adds unnecessary complexity for most use cases
2. **Feature System**: Tightly coupled and overly complex
3. **Large Models**: Subscription model has 654 lines with too many responsibilities
4. **Complex Relationships**: Plans -> Versions -> Features creates confusing hierarchy
5. **Builder Pattern**: Adds another API layer to learn
6. **Too Many Tables**: 7 tables for what should be a simple subscription system

## New Architecture Principles

### 1. Core-First Design
- **Core**: Plans & Subscriptions only
- **Optional**: Features, Usage Tracking, Events
- **Removed**: Plan versioning, complex builders

### 2. Simple API
```php
// Old way (complex)
$plan = PlanBuilder::create('premium')
    ->versionLabel('1.0.0')
    ->price(99.99, 'USD')
    ->resetPeriod(1, Period::MONTH)
    ->published()
    ->addFeature('api-calls', fn ($feature) => $feature
        ->value(1000)
        ->resetPeriod(1, Period::DAY)
    )
    ->build();

// New way (simple)
$plan = Plan::create([
    'name' => 'Premium',
    'slug' => 'premium',
    'price' => 99.99,
    'currency' => 'USD',
    'period' => 'month',
    'period_count' => 1,
]);

// Features are optional and separate
$plan->features()->attach('api-calls', ['limit' => 1000]);
```

### 3. Modular Structure
```
src/
├── Core/               # Always included
│   ├── Models/
│   │   ├── Plan.php
│   │   └── Subscription.php
│   └── Traits/
│       └── HasSubscriptions.php
├── Features/           # Optional module
│   ├── Models/
│   │   ├── Feature.php
│   │   └── FeatureUsage.php
│   └── Traits/
│       └── HasFeatures.php
└── Providers/
    └── LarasubServiceProvider.php
```

### 4. Simplified Database
```
# Core tables (2)
- plans
- subscriptions

# Optional tables (2)
- features (if features module enabled)
- feature_usage (if features module enabled)
```

## Implementation Steps

### Phase 1: Core Subscription System
1. Create simple Plan model (no versions)
2. Create streamlined Subscription model
3. Basic subscription lifecycle (subscribe, cancel, renew)
4. Simple configuration

### Phase 2: Optional Features Module
1. Separate Features into optional module
2. Simple feature attachment to plans
3. Basic usage tracking (if needed)

### Phase 3: Migration & Documentation
1. Migration guide from v3 to v4
2. Simple, clear documentation
3. Real-world examples

## Benefits
1. **80% Less Code**: Removing unnecessary complexity
2. **Easier to Learn**: Simple API, fewer concepts
3. **Flexible**: Use only what you need
4. **Maintainable**: Smaller, focused codebase
5. **Performance**: Fewer queries, simpler relationships