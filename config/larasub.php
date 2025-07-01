<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    |
    | Configure automated task scheduling for subscription-related operations.
    | When enabled, the package will automatically fire events for ending and
    | ending-soon subscriptions.
    |
    */

    'scheduling' => [
        'enabled' => env('LARASUB_SCHEDULING_ENABLED', false),
        'ending_soon_days' => env('LARASUB_SCHEDULING_ENDING_SOON_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | Database table configuration for the subscription system. You can customize
    | table names and UUID settings for each entity. Default names are provided
    | but can be overridden using environment variables.
    |
    */

    'tables' => [
        'subscribers' => [
            'uuid' => env('LARASUB_TABLE_SUBSCRIBERS_UUID', default: false),
        ],
        'plans' => [
            'name' => env('LARASUB_TABLE_PLANS', 'plans'),
            'uuid' => env('LARASUB_TABLE_PLANS_UUID', true),
        ],
        'plan_versions' => [
            'name' => env('LARASUB_TABLE_PLAN_VERSIONS', 'plan_versions'),
            'uuid' => env('LARASUB_TABLE_PLAN_VERSIONS_UUID', true),
        ],
        'features' => [
            'name' => env('LARASUB_TABLE_FEATURES', 'features'),
            'uuid' => env('LARASUB_TABLE_FEATURES_UUID', true),
        ],
        'subscriptions' => [
            'name' => env('LARASUB_TABLE_SUBSCRIPTIONS', 'subscriptions'),
            'uuid' => env('LARASUB_TABLE_SUBSCRIPTIONS_UUID', true),
        ],
        'plan_features' => [
            'name' => env('LARASUB_TABLE_PLANS_FEATURES', 'plan_features'),
            'uuid' => env('LARASUB_TABLE_PLANS_FEATURES_UUID', true),
        ],
        'subscription_feature_usages' => [
            'name' => env('LARASUB_TABLE_SUBSCRIPTION_FEATURE_USAGES', 'subscription_feature_usages'),
            'uuid' => env('LARASUB_TABLE_SUBSCRIPTION_FEATURE_USAGES_UUID', true),
        ],
        'subscription_feature_credits' => [
            'name' => env('LARASUB_TABLE_SUBSCRIPTION_FEATURE_CREDITS', 'subscription_feature_credits'),
            'uuid' => env('LARASUB_TABLE_SUBSCRIPTION_FEATURE_CREDITS_UUID', true),
            'granted_by_uuid' => env('LARASUB_TABLE_SUBSCRIPTION_FEATURE_CREDITS_GRANTED_BY_UUID', true),
        ],
        'events' => [
            'name' => env('LARASUB_TABLE_EVENTS', 'larasub_events'),
            'uuid' => env('LARASUB_TABLE_EVENTS_UUID', true),
        ],
        'eventable' => [
            'uuid' => env('LARASUB_TABLE_EVENTS_EVENTABLE_UUID', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Model class mappings for the subscription system. These classes handle
    | the business logic for plans, features, subscriptions, and related
    | entities. You can extend or replace these with your own implementations.
    |
    */

    'models' => [
        'plan' => \Err0r\Larasub\Models\Plan::class,
        'plan_version' => \Err0r\Larasub\Models\PlanVersion::class,
        'feature' => \Err0r\Larasub\Models\Feature::class,
        'subscription' => \Err0r\Larasub\Models\Subscription::class,
        'plan_feature' => \Err0r\Larasub\Models\PlanFeature::class,
        'subscription_feature_usages' => \Err0r\Larasub\Models\SubscriptionFeatureUsage::class,
        'subscription_feature_credits' => \Err0r\Larasub\Models\SubscriptionFeatureCredit::class,
        'event' => \Err0r\Larasub\Models\Event::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Resource class mappings for the subscription system. These classes handle
    | the transformation of models into JSON responses. You can extend or
    | replace these with your own implementations.
    |
    */

    'resources' => [
        'plan' => \Err0r\Larasub\Resources\PlanResource::class,
        'plan_version' => \Err0r\Larasub\Resources\PlanVersionResource::class,
        'feature' => \Err0r\Larasub\Resources\FeatureResource::class,
        'plan_feature' => \Err0r\Larasub\Resources\PlanFeatureResource::class,
        'subscription' => \Err0r\Larasub\Resources\SubscriptionResource::class,
        'subscription_feature_usage' => \Err0r\Larasub\Resources\SubscriptionFeatureUsageResource::class,
        'subscription_feature_credit' => \Err0r\Larasub\Resources\SubscriptionFeatureCreditResource::class,
    ],
];
