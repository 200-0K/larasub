<?php

namespace Err0r\Larasub\Database\Factories;

use Err0r\Larasub\Models\Feature;
use Err0r\Larasub\Models\Subscription;
use Err0r\Larasub\Models\SubscriptionFeatureCredit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Err0r\Larasub\Models\SubscriptionFeatureCredit>
 */
class SubscriptionFeatureCreditFactory extends Factory
{
    protected $model = SubscriptionFeatureCredit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'feature_id' => Feature::factory(),
            'credits' => $this->faker->randomFloat(2, 1, 1000),
            'reason' => $this->faker->optional(0.7)->randomElement([
                'Referral bonus',
                'Promotion credits',
                'Loyalty reward',
                'Support compensation',
                'Beta testing reward',
                'Manual adjustment',
            ]),
            'granted_by_type' => null,
            'granted_by_id' => null,
            'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('now', '+1 year'),
        ];
    }

    /**
     * Create credits that expire soon
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('now', '+7 days'),
        ]);
    }

    /**
     * Create expired credits
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Create credits that never expire
     */
    public function neverExpires(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    /**
     * Create credits with a specific reason
     */
    public function withReason(string $reason): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => $reason,
        ]);
    }

    /**
     * Create credits with a specific amount
     */
    public function withCredits(float $credits): static
    {
        return $this->state(fn (array $attributes) => [
            'credits' => $credits,
        ]);
    }

    /**
     * Create credits granted by a specific entity
     */
    public function grantedBy($entity): static
    {
        return $this->state(fn (array $attributes) => [
            'granted_by_type' => get_class($entity),
            'granted_by_id' => $entity->getKey(),
        ]);
    }

    /**
     * Create referral bonus credits
     */
    public function referralBonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'Referral bonus',
            'credits' => $this->faker->randomElement([5, 10, 15, 20]),
            'expires_at' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
        ]);
    }

    /**
     * Create promotional credits
     */
    public function promotional(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'Promotional credits',
            'credits' => $this->faker->randomElement([25, 50, 100, 200]),
            'expires_at' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
        ]);
    }
}