<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('course_contents')->onDelete('cascade');
            $table->enum('type', ['module', 'lesson', 'quiz', 'assignment', 'video', 'document'])->default('lesson');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('position')->default(0);
            $table->integer('duration_mins')->nullable();
            $table->json('content_data')->nullable(); // Store content-specific data
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'course_id']);
            $table->index(['course_id', 'parent_id']);
            $table->index(['course_id', 'position']);
            $table->index(['course_id', 'type']);
            $table->index(['course_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_contents');
    }
};
