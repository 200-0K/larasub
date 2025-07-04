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
            // Primary key
            if (config('larasub.use_uuid', false)) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            // Relationships
            if (config('larasub.use_uuid', false)) {
                $table->uuid('plan_id');
            } else {
                $table->unsignedBigInteger('plan_id');
            }
            
            // Polymorphic relation to subscriber (user, team, etc.)
            $table->morphs('subscriber');
            
            // Status
            $table->enum('status', ['pending', 'active', 'cancelled', 'expired'])->default('pending');
            
            // Dates
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            
            // Additional data
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index(['subscriber_type', 'subscriber_id']);
            
            // Foreign key
            $table->foreign('plan_id')
                  ->references('id')
                  ->on(config('larasub.tables.plans', 'plans'))
                  ->onDelete('restrict');
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
