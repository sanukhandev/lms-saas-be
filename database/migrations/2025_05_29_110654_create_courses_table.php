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
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->nullable()->constrained('users')->onDelete('set null');

            // Basic course information
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('slug')->nullable();

            // Course structure and scheduling
            $table->enum('schedule_level', ['course', 'module', 'chapter'])->default('chapter');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_active')->default(true);

            // Pricing
            $table->enum('access_model', ['free', 'paid', 'subscription'])->default('free');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('discounted_price', 10, 2)->nullable();
            $table->decimal('subscription_price', 10, 2)->nullable();
            $table->integer('trial_period_days')->nullable();
            $table->boolean('is_pricing_active')->default(false);
            $table->string('currency', 3)->default('USD');
            $table->enum('pricing_model', ['one_time', 'subscription', 'free'])->default('free');

            // Course details
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('duration_hours')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('preview_video_url')->nullable();
            $table->json('requirements')->nullable();
            $table->json('what_you_will_learn')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('tags')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0);

            // Tree structure fields
            $table->foreignId('parent_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->enum('content_type', ['course', 'module', 'chapter', 'lesson'])->default('course');
            $table->integer('position')->default(0);
            $table->longText('content')->nullable();
            $table->string('video_url')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->json('learning_objectives')->nullable();

            // Publishing timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamp('unpublished_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['parent_id', 'position']);
            $table->index(['content_type', 'tenant_id']);
            $table->unique(['tenant_id', 'slug']);
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
