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
            (
                config('larasub.tables.plan_versions.uuid')
                ? $table->foreignUuid('plan_version_id')
                : $table->foreignId('plan_version_id')
            )->after('plan_id')->nullable()->constrained(config('larasub.tables.plan_versions.name'))->cascadeOnDelete();
            
            // Add unique constraint to ensure each feature appears only once per plan version
            $table->unique(['plan_version_id', 'feature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('larasub.tables.plan_features.name'), function (Blueprint $table) {
            // Drop the unique constraint first
            $table->dropUnique(['plan_version_id', 'feature_id']);
            $table->dropForeign(['plan_version_id']);
            $table->dropColumn('plan_version_id');
        });
    }
};
