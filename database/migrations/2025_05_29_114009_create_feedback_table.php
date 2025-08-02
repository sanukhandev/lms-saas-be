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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tutor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('class_session_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');

            $table->tinyInteger('rating')->nullable(); // 1–5
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'class_session_id']); // prevent duplicate feedback per session
            $table->index(['tenant_id', 'student_id', 'class_session_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
