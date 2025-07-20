<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable(); // learning objectives
            $table->text('prerequisites')->nullable();
            $table->text('materials_needed')->nullable();
            $table->text('assessment_methods')->nullable();
            $table->integer('estimated_duration')->nullable(); // in minutes
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('schedule')->nullable(); // weekly schedule
            $table->json('milestones')->nullable(); // course milestones
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'course_id']);
            $table->index(['course_id', 'instructor_id']);
            $table->index(['instructor_id', 'status']);
            $table->index(['status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_plans');
    }
};
