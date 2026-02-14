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
        Schema::table('utility_company', function (Blueprint $table) {
            // Add a tinyint column with default 1 right after category_slug
            $table->tinyInteger('is_active')->default(1)->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_company', function (Blueprint $table) {
            // Drop the column if rolling back
            if (Schema::hasColumn('utility_company', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
