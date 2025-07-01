<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('larasub.tables.subscription_feature_credits.name'), function (Blueprint $table) {
            if (config('larasub.tables.subscription_feature_credits.uuid')) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            (
                config('larasub.tables.subscriptions.uuid')
                ? $table->foreignUuid('subscription_id')
                : $table->foreignId('subscription_id')
            )->constrained(config('larasub.tables.subscriptions.name'))->cascadeOnDelete();

            (
                config('larasub.tables.features.uuid')
                ? $table->foreignUuid('feature_id')
                : $table->foreignId('feature_id')
            )->constrained(config('larasub.tables.features.name'))->cascadeOnDelete();

            $table->decimal('credits', 15, 4)->default(0);
            $table->string('reason')->nullable();
            
            // Polymorphic relationship for who granted the credits
            (
                config('larasub.tables.subscription_feature_credits.granted_by_uuid', true)
                ? $table->nullableUuidMorphs('granted_by')
                : $table->nullableMorphs('granted_by')
            );

            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['subscription_id', 'feature_id'], 'idx_subscription_feature');
            $table->index('expires_at', 'idx_expires_at');
            $table->index(['subscription_id', 'feature_id', 'expires_at'], 'idx_sub_feat_exp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('larasub.tables.subscription_feature_credits.name'));
    }
};