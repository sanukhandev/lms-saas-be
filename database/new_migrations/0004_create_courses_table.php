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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('instructor_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Basic course information
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('slug')->nullable();
            $table->string('level')->nullable(); // beginner, intermediate, advanced
            $table->enum('status', ['draft', 'published', 'archived', 'pending_review'])->default('draft');
            $table->boolean('is_active')->default(true);
            
            // Tree structure fields for hierarchical organization
            $table->foreignId('parent_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->string('content_type')->default('course'); // course, module, chapter, lesson
            $table->integer('position')->default(0);
            $table->longText('content')->nullable(); // For storing actual content
            $table->json('learning_objectives')->nullable();
            
            // Media-related fields
            $table->string('thumbnail_url')->nullable();
            $table->string('preview_video_url')->nullable();
            $table->string('video_url')->nullable();
            
            // Duration and scheduling
            $table->float('duration_hours')->nullable();
            $table->float('duration_minutes')->nullable();
            $table->string('schedule_level')->nullable();
            
            // Pricing and monetization
            $table->string('access_model')->nullable(); // one-time, subscription, free
            $table->string('pricing_model')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('discounted_price', 10, 2)->nullable();
            $table->decimal('subscription_price', 10, 2)->nullable();
            $table->integer('trial_period_days')->nullable();
            $table->boolean('is_pricing_active')->default(true);
            
            // SEO and tagging
            $table->text('meta_description')->nullable();
            $table->json('tags')->nullable();
            $table->text('requirements')->nullable();
            $table->text('what_you_will_learn')->nullable();
            
            // Statistics and ratings
            $table->decimal('average_rating', 3, 2)->nullable();
            
            // Publication timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamp('unpublished_at')->nullable();
            $table->timestamps();
            
            // Add optimized indexes
            $table->index(['tenant_id', 'status', 'is_active']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'parent_id', 'content_type']);
            $table->index(['parent_id', 'position']);
            $table->index(['tenant_id', 'slug']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
