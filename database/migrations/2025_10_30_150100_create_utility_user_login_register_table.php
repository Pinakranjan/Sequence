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
        Schema::create('utility_user_login_register', function (Blueprint $table) {
            // Primary key
            $table->increments('id');

            // Foreign references (nullable where specified)
            $table->unsignedBigInteger('company_id')->nullable();

            // Timestamps with millisecond precision where applicable
            $table->dateTime('last_connected_time', 3)->useCurrent();
            $table->dateTime('login_time', 3)->useCurrent();

            // logout_time is varchar(100) per spec (can contain non-datetime info)
            $table->string('logout_time', 100)->nullable();

            $table->dateTime('session_end_time', 3)->nullable();
            $table->string('session_end_type', 20)->nullable();
            $table->string('system_name', 200)->nullable();

            // User + Session
            $table->unsignedBigInteger('user_id');
            $table->string('session_id', 100)->nullable();

            // Indexes
            $table->index('user_id', 'IX_USER_ID');
            $table->index('company_id', 'IX_COMPANY_ID');
        });

        // Add foreign keys conditionally (to avoid failing if tables not present yet in this step-by-step flow)
        Schema::table('utility_user_login_register', function (Blueprint $table) {
            // user_id -> users(id) (CASCADE on delete)
            if (Schema::hasTable('users')) {
                $table->foreign('user_id', 'FK_USER_ID_Login')
                    ->references('id')->on('users')
                    ->cascadeOnDelete();
            }
            // business_id -> utility_company(id) (RESTRICT on delete)
            if (Schema::hasTable('utility_company')) {
                $table->foreign('company_id', 'FK_COMPANY_ID_Login')
                    ->references('id')->on('utility_company')
                    ->restrictOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop FKs if exist
        Schema::table('utility_user_login_register', function (Blueprint $table) {
            try {
                $table->dropForeign('FK_USER_ID_Login');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropForeign('FK_COMPANY_ID_Login');
            } catch (\Throwable $e) {
            }
        });
        Schema::dropIfExists('utility_user_login_register');
    }
};
