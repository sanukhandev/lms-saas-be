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
        Schema::create('course_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->decimal('amount_paid', 10, 2);
            $table->string('currency', 3);
            $table->string('invoice_id')->nullable();
            $table->timestamp('access_start_date');
            $table->timestamp('access_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add optimized indexes
            $table->index(['tenant_id', 'student_id', 'course_id']);
            $table->index(['student_id', 'is_active']);
            $table->index(['course_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_purchases');
    }
};
