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
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('content_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->integer('time_spent_seconds')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('quiz_scores')->nullable();
            $table->json('activity_log')->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamps();
            
            // Add optimized indexes
            $table->index(['tenant_id', 'user_id', 'course_id']);
            $table->index(['user_id', 'course_id', 'content_id']);
            $table->index(['course_id', 'status']);
            $table->unique(['user_id', 'content_id'], 'unique_user_content_progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
