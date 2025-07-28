<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_content_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('duration_minutes'); // exam duration in minutes
            $table->integer('total_marks')->default(0);
            $table->decimal('passing_marks', 5, 2)->default(0); // passing score
            $table->integer('max_attempts')->default(1);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('show_results_immediately')->default(true);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->json('settings')->nullable(); // additional exam settings
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'course_id']);
            $table->index(['course_id', 'status']);
            $table->index(['status', 'available_from', 'available_until']);
            $table->index(['course_content_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
