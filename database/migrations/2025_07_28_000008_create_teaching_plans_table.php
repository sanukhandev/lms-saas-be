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
        Schema::create('teaching_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('schedule')->nullable(); // For storing schedule preferences
            $table->json('objectives')->nullable();
            $table->json('resources')->nullable();
            $table->json('assessments')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamps();
            
            // Add optimized indexes
            $table->index(['tenant_id', 'course_id']);
            $table->index(['tenant_id', 'instructor_id']);
            $table->index(['course_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_plans');
    }
};
