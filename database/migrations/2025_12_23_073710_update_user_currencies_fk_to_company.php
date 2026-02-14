<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Updates the utility_user_currencies table:
     * 1. Drops the foreign key constraint to utility_businesses
     * 2. Renames business_id column to company_id
     * 3. Adds new foreign key constraint to utility_company
     * 4. Drops the utility_businesses table (no longer needed)
     */
    public function up(): void
    {
        // Step 1: Drop existing foreign key on business_id
        Schema::table('utility_user_currencies', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        // Step 2: Rename business_id to company_id
        Schema::table('utility_user_currencies', function (Blueprint $table) {
            $table->renameColumn('business_id', 'company_id');
        });

        // Step 3: Add new foreign key to utility_company
        Schema::table('utility_user_currencies', function (Blueprint $table) {
            $table->foreign('company_id')
                ->references('id')
                ->on('utility_company')
                ->cascadeOnDelete();
        });

        // Step 4: Drop the utility_businesses table (no longer used)
        Schema::dropIfExists('utility_businesses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate utility_businesses table
        Schema::create('utility_businesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_subscribe_id')->nullable();
            $table->unsignedBigInteger('business_category_id')->nullable();
            $table->string('companyName')->nullable();
            $table->text('address')->nullable();
            $table->string('phoneNumber')->nullable();
            $table->string('pictureUrl')->nullable();
            $table->date('will_expire')->nullable();
            $table->date('subscriptionDate')->nullable();
            $table->double('remainingShopBalance')->nullable();
            $table->double('shopOpeningBalance')->nullable();
            $table->string('vat_name')->nullable();
            $table->string('vat_no')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // Drop foreign key on company_id
        Schema::table('utility_user_currencies', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        // Rename company_id back to business_id
        Schema::table('utility_user_currencies', function (Blueprint $table) {
            $table->renameColumn('company_id', 'business_id');
        });

        // Re-add foreign key to utility_businesses
        Schema::table('utility_user_currencies', function (Blueprint $table) {
            $table->foreign('business_id')
                ->references('id')
                ->on('utility_businesses')
                ->cascadeOnDelete();
        });
    }
};
