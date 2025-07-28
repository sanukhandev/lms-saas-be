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
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('score');
            $table->integer('max_score');
            $table->decimal('percentage', 5, 2);
            $table->boolean('passed')->default(false);
            $table->integer('attempt_number')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent_seconds')->nullable();
            $table->json('answers')->nullable(); // User's answers
            $table->json('question_results')->nullable(); // Results for each question
            $table->text('feedback')->nullable(); // Overall feedback
            $table->json('meta_data')->nullable();
            $table->timestamps();
            
            // Add optimized indexes
            $table->index(['tenant_id', 'exam_id', 'user_id']);
            $table->index(['user_id', 'exam_id', 'attempt_number']);
            $table->index(['exam_id', 'passed']);
            $table->unique(['exam_id', 'user_id', 'attempt_number'], 'unique_exam_user_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
