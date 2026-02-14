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
        Schema::table('users', function (Blueprint $table) {
            // Add is_locked (0/1) default 0 after status
            $table->boolean('is_locked')->default(false)->after('status');

            // Add lastmodified_by (text(100) requested) -> using string(100) which maps to VARCHAR(100)
            $table->string('lastmodified_by', 100)->nullable()->after('updated_at');

            // Add company_id (nullable) to link to utility_company
            $table->unsignedBigInteger('company_id')->nullable()->after('lastmodified_by');
        });

        Schema::table('users', function (Blueprint $table) {
            // Foreign key: company_id -> utility_company(id), RESTRICT on delete
            $table->foreign('company_id')
                ->references('id')
                ->on('utility_company')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the foreign key first, then the columns
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'lastmodified_by', 'is_locked']);
        });
    }
};
