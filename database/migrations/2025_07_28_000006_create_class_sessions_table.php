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
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('content_id')->nullable()->constrained('course_contents')->onDelete('set null');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at');
            $table->integer('duration_mins')->nullable();
            $table->string('location')->nullable(); // Could be physical or virtual
            $table->string('meeting_url')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->boolean('is_recorded')->default(false);
            $table->string('recording_url')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->json('materials')->nullable(); // Links to materials
            $table->json('meta_data')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // Daily, weekly, etc.
            $table->timestamps();
            
            // Add optimized indexes
            $table->index(['tenant_id', 'course_id']);
            $table->index(['tenant_id', 'tutor_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('scheduled_at');
            $table->index('content_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
