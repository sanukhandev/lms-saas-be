<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('domain')->unique();
                $table->string('slug')->unique();
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
                $table->json('settings')->nullable();
                $table->timestamps();
    
                // Indexes
                $table->index(['status', 'domain']);
                $table->index('slug');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
