<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('content_id')->nullable(); // Will add foreign key later
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('scheduled_at');
            $table->integer('duration_mins')->default(60);
            $table->string('meeting_url')->nullable();
            $table->boolean('is_recorded')->default(false);
            $table->string('recording_url')->nullable();
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'course_id']);
            $table->index(['course_id', 'scheduled_at']);
            $table->index(['tutor_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['tenant_id', 'status']);
            $table->index(['content_id']); // Add index for content_id
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
