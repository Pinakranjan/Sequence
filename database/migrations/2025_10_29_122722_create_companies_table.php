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
        Schema::create('utility_company', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('address', 500)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->unsignedInteger('approved_users')->default(5);
            $table->string('created_by', 100)->nullable()->index();
            $table->string('lastmodified_by', 100)->nullable()->index();
            $table->string('image', 200)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_company');
    }
};
