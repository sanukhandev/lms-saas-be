<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // who gave feedback
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('type', ['course_rating', 'instructor_rating', 'platform_feedback', 'suggestion']);
            $table->integer('rating')->nullable(); // 1-5 star rating
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_featured')->default(false); // featured reviews
            $table->enum('status', ['pending', 'approved', 'rejected', 'hidden'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'course_id']);
            $table->index(['course_id', 'status', 'rating']);
            $table->index(['instructor_id', 'status', 'rating']);
            $table->index(['user_id', 'type']);
            $table->index(['type', 'status']);
            $table->index(['rating']);
            $table->index(['is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
