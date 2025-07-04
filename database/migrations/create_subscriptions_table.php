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
        Schema::create(config('larasub.tables.subscriptions', 'subscriptions'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained(config('larasub.tables.plans', 'plans'));
            $table->morphs('subscriber');
            $table->enum('status', ['pending', 'active', 'cancelled', 'expired'])->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subscriber_type', 'subscriber_id']);
            $table->index(['status', 'ends_at']);
            $table->index('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('larasub.tables.subscriptions', 'subscriptions'));
    }
};
