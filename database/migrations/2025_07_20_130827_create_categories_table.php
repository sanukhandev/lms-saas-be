<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('meta_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
