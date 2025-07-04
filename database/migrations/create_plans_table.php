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
        Schema::create(config('larasub.tables.plans', 'plans'), function (Blueprint $table) {
            // Primary key
            if (config('larasub.use_uuid', false)) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            // Core fields
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default(config('larasub.default_currency', 'USD'));
            
            // Billing period
            $table->enum('period', ['day', 'week', 'month', 'year'])->default('month');
            $table->unsignedInteger('period_count')->default(1);
            
            // Additional data
            $table->json('metadata')->nullable();
            
            // Status and ordering
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('larasub.tables.plans', 'plans'));
    }
};
