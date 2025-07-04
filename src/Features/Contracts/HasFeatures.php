<?php

namespace Err0r\Larasub\Features\Contracts;

interface HasFeatures
{
    /**
     * Check if the entity can use a specific feature.
     */
    public function canUseFeature(string $feature): bool;

    /**
     * Get the usage for a specific feature.
     */
    public function getFeatureUsage(string $feature): int;

    /**
     * Record usage of a feature.
     */
    public function recordFeatureUsage(string $feature, int $uses = 1): bool;

    /**
     * Get the limit for a specific feature.
     */
    public function getFeatureLimit(string $feature): ?int;

    /**
     * Check if a feature has remaining uses.
     */
    public function hasFeatureRemaining(string $feature): bool;
}