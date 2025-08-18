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
        Schema::table('course_contents', function (Blueprint $table) {
            // Extend content types to support rich content
            $table->dropColumn('type');
        });

        Schema::table('course_contents', function (Blueprint $table) {
            // Add new content types for rich content editor
            $table->enum('type', [
                'module',
                'chapter',
                'lesson',
                'video',
                'document',
                'quiz',
                'assignment',
                'text',
                'live_session'
            ])->default('lesson')->after('tenant_id');

            // Rich content fields
            $table->longText('content')->nullable()->after('description'); // Rich text content
            $table->json('content_data')->nullable()->after('content'); // Flexible data storage
            $table->string('video_url')->nullable()->after('content_data'); // Video embed URL
            $table->string('file_path')->nullable()->after('video_url'); // Uploaded file path
            $table->string('file_type')->nullable()->after('file_path'); // MIME type
            $table->bigInteger('file_size')->nullable()->after('file_type'); // File size in bytes
            $table->json('learning_objectives')->nullable()->after('file_size'); // Learning goals

            // Content status and publishing
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->after('learning_objectives');
            $table->boolean('is_required')->default(false)->after('status'); // Required for course completion
            $table->boolean('is_free')->default(false)->after('is_required'); // Free preview content
            $table->timestamp('published_at')->nullable()->after('is_free');

            // Content settings
            $table->integer('estimated_duration')->nullable()->after('duration_mins'); // In minutes
            $table->integer('sort_order')->default(0)->after('position'); // Additional ordering field
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_contents', function (Blueprint $table) {
            // Remove new fields
            $table->dropColumn([
                'content',
                'content_data',
                'video_url',
                'file_path',
                'file_type',
                'file_size',
                'learning_objectives',
                'status',
                'is_required',
                'is_free',
                'published_at',
                'estimated_duration',
                'sort_order'
            ]);

            // Restore original type enum
            $table->dropColumn('type');
        });

        Schema::table('course_contents', function (Blueprint $table) {
            $table->enum('type', ['module', 'chapter'])->after('tenant_id');
        });
    }
};
