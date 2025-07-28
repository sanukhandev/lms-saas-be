<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_content_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['document', 'video', 'audio', 'image', 'archive', 'link', 'other']);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_extension')->nullable();
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->string('mime_type')->nullable();
            $table->string('external_url')->nullable(); // for external links
            $table->boolean('is_downloadable')->default(true);
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'course_id']);
            $table->index(['course_id', 'type']);
            $table->index(['course_content_id']);
            $table->index(['type']);
            $table->index(['is_required']);
            $table->index(['order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_materials');
    }
};
