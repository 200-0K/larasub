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
        Schema::create(config('larasub.tables.subscriptions.name'), function (Blueprint $table) {
            if (config('larasub.tables.subscriptions.uuid')) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            (
                config('larasub.tables.subscribers.uuid')
                ? $table->uuidMorphs('subscriber')
                : $table->morphs('subscriber')
            );

            (
                config('larasub.tables.plans.uuid')
                ? $table->foreignUuid('plan_id')
                : $table->foreignId('plan_id')
            )->constrained(config('larasub.tables.plans.name'))->cascadeOnDelete();

            (
                config('larasub.tables.subscriptions.uuid')
                ? $table->foreignUuid('renewed_from_id')
                : $table->foreignId('renewed_from_id')
            )->nullable()->constrained(config('larasub.tables.subscriptions.name'));

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('larasub.tables.subscriptions.name'));
    }
};
