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
        Schema::create('instructor_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->string('location')->nullable(); // HQ / remote / etc.
            $table->enum('status', ['present', 'late', 'no_show'])->default('present');
            $table->timestamps();

            $table->unique(['class_session_id', 'instructor_id']); // 1 record per tutor per session
            $table->index(['tenant_id', 'class_session_id']);
            $table->index(['class_session_id', 'instructor_id', 'status'],'instructor_attendance_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_attendances');
    }
};
