<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('instructor_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Basic Course Information
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('slug')->nullable();
            
            // Course Settings
            $table->enum('status', ['created', 'draft', 'published', 'archived'])->default('created');
            $table->boolean('is_active')->default(false);
            $table->string('level')->default('beginner'); // beginner, intermediate, advanced
            $table->integer('duration_hours')->nullable();
            $table->string('schedule_level')->nullable();
            
            // Pricing
            $table->string('access_model')->default('free'); // free, paid, subscription
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('subscription_price', 10, 2)->nullable();
            $table->integer('trial_period_days')->default(0);
            $table->boolean('is_pricing_active')->default(false);
            $table->string('currency', 3)->default('USD');
            
            // Media & Content
            $table->string('thumbnail_url')->nullable();
            $table->string('preview_video_url')->nullable();
            
            // Learning Details
            $table->text('requirements')->nullable();
            $table->text('what_you_will_learn')->nullable();
            
            // SEO & Metadata
            $table->string('meta_description')->nullable();
            $table->text('tags')->nullable();
            
            // Ratings & Analytics
            $table->decimal('average_rating', 3, 2)->nullable();
            
            $table->timestamps();

            // Indexes
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'instructor_id']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'level']);
            $table->index(['tenant_id', 'access_model']);
            $table->index('average_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
