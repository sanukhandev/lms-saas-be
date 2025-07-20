<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_content_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('progress_percentage', 5, 2)->default(0); // 0.00 to 100.00
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'skipped'])->default('not_started');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent')->default(0); // in seconds
            $table->integer('attempts')->default(0);
            $table->json('metadata')->nullable(); // additional tracking data
            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'course_id', 'course_content_id']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['course_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['progress_percentage']);
            $table->index(['completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
