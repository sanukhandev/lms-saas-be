<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('key'); // setting key
            $table->text('value')->nullable(); // setting value (can be JSON)
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'array'])->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // can be accessed by frontend
            $table->timestamps();

            // Indexes
            $table->unique(['tenant_id', 'key']);
            $table->index(['tenant_id', 'is_public']);
            $table->index(['key']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
