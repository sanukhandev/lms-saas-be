<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->text('question');
            $table->enum('type', ['multiple_choice', 'true_false', 'short_answer', 'essay', 'fill_blank']);
            $table->json('options')->nullable(); // for multiple choice questions
            $table->json('correct_answers'); // correct answer(s)
            $table->decimal('marks', 5, 2)->default(1);
            $table->integer('order')->default(0);
            $table->text('explanation')->nullable(); // explanation for the answer
            $table->string('image_url')->nullable(); // question image
            $table->boolean('is_required')->default(true);
            $table->json('metadata')->nullable(); // additional question data
            $table->timestamps();

            // Indexes
            $table->index(['exam_id', 'order']);
            $table->index(['exam_id', 'type']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
