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
            // Add is_locked (0/1) default 0 right after image so it appears before is_active
            $table->tinyInteger('is_locked')->default(0)->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_company', function (Blueprint $table) {
            if (Schema::hasColumn('utility_company', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });
    }
};
