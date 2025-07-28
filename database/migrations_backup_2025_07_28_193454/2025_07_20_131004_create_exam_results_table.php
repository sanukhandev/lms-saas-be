<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('attempt_number')->default(1);
            $table->decimal('score', 5, 2)->default(0); // obtained marks
            $table->decimal('percentage', 5, 2)->default(0); // percentage score
            $table->enum('status', ['in_progress', 'completed', 'submitted', 'graded'])->default('in_progress');
            $table->boolean('passed')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->integer('time_taken')->nullable(); // in seconds
            $table->json('answers'); // student answers
            $table->json('question_scores')->nullable(); // individual question scores
            $table->text('feedback')->nullable(); // instructor feedback
            $table->timestamps();

            // Indexes
            $table->index(['exam_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['exam_id', 'status']);
            $table->index(['exam_id', 'user_id', 'attempt_number']);
            $table->index(['percentage']);
            $table->index(['passed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
