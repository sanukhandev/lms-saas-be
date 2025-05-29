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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('exam_result_id')->nullable()->constrained()->onDelete('set null');
            $table->string('certificate_no')->unique(); // for public verification
            $table->string('template_slug')->nullable(); // Blade view: /resources/views/certificates/{slug}.blade.php
            $table->string('pdf_path')->nullable(); // path to generated PDF
            $table->boolean('is_verified')->default(true); // can be toggled
            $table->timestamps();
            $table->index(['tenant_id', 'course_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
