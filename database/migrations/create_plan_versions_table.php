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
        Schema::create(config('larasub.tables.plan_versions.name'), function (Blueprint $table) {
            if (config('larasub.tables.plan_versions.uuid')) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            (
                config('larasub.tables.plans.uuid')
                ? $table->foreignUuid('plan_id')
                : $table->foreignId('plan_id')
            )->constrained(config('larasub.tables.plans.name'))->cascadeOnDelete();

            $table->unsignedInteger('version_number')->default(1);
            $table->string('version_label')->nullable(); // Optional display label like "2.0.0", "Winter 2024"
            $table->decimal('price')->default('0.0');
            $table->json('currency');
            $table->unsignedSmallInteger('reset_period')->nullable();
            $table->string('reset_period_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['plan_id', 'version_number']);
            $table->index(['plan_id', 'is_active', 'published_at']);
            $table->index(['plan_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('larasub.tables.plan_versions.name'));
    }
}; 