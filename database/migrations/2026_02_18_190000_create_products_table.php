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
        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('utility_company')->restrictOnDelete();
            $table->string('product_code', 10);
            $table->string('product_name', 100);
            $table->tinyInteger('product_type')->default(0);
            $table->tinyInteger('is_active')->default(1);
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
        Schema::dropIfExists('master_products');
    }
};
