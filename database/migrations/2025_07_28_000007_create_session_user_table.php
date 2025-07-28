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
        Schema::create('session_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained('class_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('attendance_status', ['present', 'absent', 'late', 'excused'])->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('location')->nullable(); // For tracking where the student attended from
            $table->json('meta_data')->nullable();
            $table->timestamps();
            
            // Add optimized indexes
            $table->index(['class_session_id', 'user_id']);
            $table->index(['class_session_id', 'attendance_status']);
            $table->unique(['class_session_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_user');
    }
};
