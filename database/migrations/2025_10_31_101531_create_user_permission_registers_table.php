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
        Schema::create('utility_user_permission_register', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('form_id')->constrained('utility_forms')->restrictOnDelete();
            $table->tinyInteger('is_add')->default(0);
            $table->tinyInteger('is_edit')->default(0);
            $table->tinyInteger('is_delete')->default(0);
            $table->tinyInteger('is_view')->default(0);
            $table->tinyInteger('is_viewatt')->default(0);        
            $table->string('created_by', 100)->nullable()->index();
            $table->string('lastmodified_by', 100)->nullable()->index();                
            // Composite index for faster lookups by user and form
            $table->index(['user_id', 'form_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_user_permission_register');
    }
};
