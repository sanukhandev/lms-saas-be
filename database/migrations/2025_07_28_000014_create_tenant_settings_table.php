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
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('key');
            $table->json('value')->nullable();
            $table->string('group')->default('general'); // For organizing settings: general, branding, features, security, etc.
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Whether setting is visible to non-admin users
            $table->boolean('is_system')->default(false); // Whether it's a system setting that shouldn't be changed
            $table->timestamps();
            
            // Add optimized indexes
            $table->index(['tenant_id', 'key']);
            $table->index(['tenant_id', 'group']);
            $table->unique(['tenant_id', 'key'], 'unique_tenant_setting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
