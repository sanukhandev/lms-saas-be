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
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_id')->nullable()->constrained('course_contents')->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['pdf', 'doc', 'video', 'link', 'other'])->default('pdf');

            $table->string('file_path')->nullable(); // uploaded file
            $table->string('external_url')->nullable(); // YouTube / Google Drive etc.
            $table->boolean('is_public')->default(false);

            $table->timestamps();

            $table->index(['tenant_id', 'course_id', 'type'],'course_materials_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_materials');
    }
};
