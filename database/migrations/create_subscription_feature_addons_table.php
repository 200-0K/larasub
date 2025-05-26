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
        Schema::create(config('larasub.tables.subscription_feature_addons.name'), function (Blueprint $table) {
            if (config('larasub.tables.subscription_feature_addons.uuid')) {
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

            // Generic value field for both consumable and non-consumable features
            // Consistent with how plan_features table handles values
            $table->text('value')->nullable();

            // Reference for payment tracking, etc.
            $table->string('reference')->nullable();

            // When this add-on expires
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('larasub.tables.subscription_feature_addons.name'));
    }
};
