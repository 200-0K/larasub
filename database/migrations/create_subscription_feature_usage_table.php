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
        Schema::create(config('larasub.tables.subscription_feature_usage.name'), function (Blueprint $table) {
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

            $table->string('value');

            $table->timestamps();

            $table->primary(['subscription_id', 'feature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('larasub.tables.subscription_feature_usage.name'));
    }
};
