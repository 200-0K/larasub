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
            // Add boolean field to control feature visibility to users
            $table->boolean('is_hidden')->default(false)->after('reset_period_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('larasub.tables.plan_features.name'), function (Blueprint $table) {
            $table->dropColumn(['is_hidden']);
        });
    }
};
