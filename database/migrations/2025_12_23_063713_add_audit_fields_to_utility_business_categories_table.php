<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('utility_business_categories', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('status');
            $table->string('lastmodified_by')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_business_categories', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'lastmodified_by']);
        });
    }
};
