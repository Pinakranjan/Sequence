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
        Schema::create('utility_user_currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('utility_businesses')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('utility_currencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('country_name')->nullable();
            $table->string('code');
            $table->double('rate')->nullable();
            $table->string('symbol')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_user_currencies');
    }
};
