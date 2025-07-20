<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_session_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['present', 'absent', 'late', 'cancelled'])->default('present');
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->text('notes')->nullable();
            $table->string('location')->nullable(); // IP or physical location
            $table->integer('session_duration')->nullable(); // in minutes
            $table->timestamps();

            // Indexes
            $table->unique(['instructor_id', 'class_session_id']);
            $table->index(['tenant_id', 'instructor_id']);
            $table->index(['class_session_id', 'status']);
            $table->index(['instructor_id', 'status']);
            $table->index(['check_in_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_attendances');
    }
};
