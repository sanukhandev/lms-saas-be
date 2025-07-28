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
        // First check if the courses table exists
        if (Schema::hasTable('courses')) {
            // Check if the content_type column doesn't already exist
            if (!Schema::hasColumn('courses', 'content_type')) {
                Schema::table('courses', function (Blueprint $table) {
                    // Add parent_id for hierarchical structure
                    $table->foreignId('parent_id')->nullable()->constrained('courses')->onDelete('cascade');
                    
                    // Add content type field for differentiating between course types
                    $table->string('content_type')->default('course'); // Possible values: course, module, chapter, lesson, etc.
                    
                    // Add position field for ordering siblings
                    $table->integer('position')->default(0);
                    
                    // Add content field for chapter-specific content
                    $table->longText('content')->nullable();
                    
                    // Add learning objectives field for chapters
                    $table->json('learning_objectives')->nullable();
                    
                    // Add video URL for video content
                    $table->string('video_url')->nullable();
                    
                    // Add duration in minutes for chapters/lessons
                    $table->float('duration_minutes')->nullable();
                    
                    // Index for faster queries
                    $table->index(['tenant_id', 'parent_id', 'content_type']);
                    $table->index(['parent_id', 'position']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'parent_id', 'content_type']);
            $table->dropIndex(['parent_id', 'position']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'parent_id',
                'content_type',
                'position',
                'content',
                'learning_objectives',
                'video_url',
                'duration_minutes'
            ]);
        });
    }
};
