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
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_id')->constrained('course_contents')->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->enum('class_type', ['1v1', '1vN', 'NvN'])->default('1vN');
            $table->date('planned_date')->nullable();
            $table->integer('duration_mins')->nullable(); // optional override
            $table->timestamps();

            $table->unique(['tenant_id', 'course_id', 'content_id']); // prevent duplicate plans
            $table->index(['tenant_id', 'course_id', 'planned_date']);
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
