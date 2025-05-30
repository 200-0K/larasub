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
        Schema::table(config('larasub.tables.plan_features.name'), function (Blueprint $table) {
            if (Schema::hasColumn(config('larasub.tables.plan_features.name'), 'plan_id')) {
                $table->dropForeign(['plan_id']);
                $table->dropUnique(['plan_id', 'feature_id']);
                $table->dropColumn('plan_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('larasub.tables.plan_features.name'), function (Blueprint $table) {
            (
                config('larasub.tables.plans.uuid')
                ? $table->foreignUuid('plan_id')
                : $table->foreignId('plan_id')
            )->before('plan_version_id')->nullable()->constrained(config('larasub.tables.plans.name'))->cascadeOnDelete();

            // Restore the unique constraint
            $table->unique(['plan_id', 'feature_id']);
        });
    }
};
