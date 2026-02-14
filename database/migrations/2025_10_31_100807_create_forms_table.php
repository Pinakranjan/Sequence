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
        Schema::create('utility_forms', function (Blueprint $table) {
            $table->id();
            $table->string('form_name', 50);
            $table->string('group_name', 50);
            $table->string('subgroup_name', 50);
            $table->tinyInteger('is_add')->default(1);
            $table->tinyInteger('is_edit')->default(1);
            $table->tinyInteger('is_delete')->default(0);
            $table->tinyInteger('is_view')->default(1);
            $table->tinyInteger('is_viewatt')->default(1);
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('is_assignable')->default(0);
            $table->tinyInteger('is_realesed')->default(0);
            $table->string('created_by', 100)->nullable()->index();
            $table->string('lastmodified_by', 100)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_forms');
    }
};
