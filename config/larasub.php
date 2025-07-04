<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Larasub Configuration
    |--------------------------------------------------------------------------
    |
    | Simple subscription management for Laravel
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Use UUID
    |--------------------------------------------------------------------------
    |
    | Set to true if you want to use UUIDs instead of auto-incrementing IDs
    |
    */
    'use_uuid' => false,

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names if needed
    |
    */
    'tables' => [
        'plans' => 'plans',
        'subscriptions' => 'subscriptions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for plans
    |
    */
    'default_currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Features Module
    |--------------------------------------------------------------------------
    |
    | Enable the optional features module for feature-based subscriptions
    |
    */
    'features' => [
        'enabled' => false,
        'tables' => [
            'features' => 'features',
            'plan_features' => 'plan_features',
            'feature_usage' => 'feature_usage',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Defaults
    |--------------------------------------------------------------------------
    |
    | Default values for subscriptions
    |
    */
    'subscription_defaults' => [
        'trial_days' => 0,
        'auto_renew' => true,
    ],
];
