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
        Schema::create('course_contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // 'module', 'chapter', 'lesson', 'quiz', etc.
            $table->integer('position')->default(0);
            $table->integer('duration_mins')->nullable();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('course_contents')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->json('metadata')->nullable();
            $table->string('video_url')->nullable();
            $table->string('content_url')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();
            
            // Add optimized indexes
            $table->index(['course_id', 'type', 'position']);
            $table->index(['parent_id', 'position']);
            $table->index(['tenant_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_contents');
    }
};
