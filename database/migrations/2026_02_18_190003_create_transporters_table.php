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
        Schema::create('master_transporters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('utility_company')->restrictOnDelete();
            $table->string('transporter_code', 10);
            $table->string('transporter_name', 100);
            $table->string('correspondence_address', 500)->nullable();
            $table->string('billing_address', 500)->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('mobile_no', 15)->nullable();
            $table->string('email', 50)->nullable();
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('master_transporters');
    }
};
