<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['instructor', 'student'])->default('student');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'completed'])->default('active');
            $table->timestamps();

            // Indexes
            $table->unique(['course_id', 'user_id', 'role']);
            $table->index(['course_id', 'role']);
            $table->index(['user_id', 'role']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_user');
    }
};
