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
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('jti')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_device_id')->constrained('user_devices')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->foreignId('rotated_from_id')->nullable()->constrained('refresh_tokens')->nullOnDelete();
            $table->string('last_ip', 45)->nullable();
            $table->text('last_user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'user_device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
