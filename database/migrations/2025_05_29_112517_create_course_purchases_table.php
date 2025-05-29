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
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('currency', 3)->default('AED');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');

            $table->date('access_start_date')->nullable();
            $table->date('access_expires_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'course_id']); // One purchase per student per course
            $table->index(['tenant_id', 'student_id', 'course_id']);
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
