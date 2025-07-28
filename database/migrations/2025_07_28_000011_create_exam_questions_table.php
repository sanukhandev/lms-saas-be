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
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->text('question_text');
            $table->enum('question_type', ['multiple_choice', 'true_false', 'short_answer', 'essay', 'matching'])->default('multiple_choice');
            $table->json('options')->nullable(); // For multiple choice, matching
            $table->json('correct_answer')->nullable(); // The correct answer(s)
            $table->integer('points')->default(1);
            $table->text('feedback')->nullable(); // Feedback to show after answering
            $table->text('hint')->nullable();
            $table->integer('position')->default(0); // For ordering questions
            $table->json('meta_data')->nullable();
            $table->timestamps();
            
            // Add optimized indexes
            $table->index(['tenant_id', 'exam_id']);
            $table->index(['exam_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
