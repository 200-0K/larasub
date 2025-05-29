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
        Schema::table(config('larasub.tables.plans.name'), function (Blueprint $table) {
            // Drop columns that have been moved to plan_versions table
            if (Schema::hasColumn(config('larasub.tables.plans.name'), 'price')) {
                $table->dropColumn('price');
            }
            if (Schema::hasColumn(config('larasub.tables.plans.name'), 'currency')) {
                $table->dropColumn('currency');
            }
            if (Schema::hasColumn(config('larasub.tables.plans.name'), 'reset_period')) {
                $table->dropColumn('reset_period');
            }
            if (Schema::hasColumn(config('larasub.tables.plans.name'), 'reset_period_type')) {
                $table->dropColumn('reset_period_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('larasub.tables.plans.name'), function (Blueprint $table) {
            // Re-add the columns that were moved to plan_versions table
            $table->decimal('price')->default('0.0');
            $table->json('currency');
            $table->unsignedSmallInteger('reset_period')->nullable();
            $table->string('reset_period_type')->nullable();
        });
    }
}; 