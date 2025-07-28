<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('attendance_status', ['present', 'absent', 'late', 'excused'])->default('absent');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('location')->nullable(); // IP address or location info
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['class_session_id', 'user_id']);
            $table->index(['class_session_id', 'attendance_status']);
            $table->index(['user_id', 'attendance_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_user');
    }
};
