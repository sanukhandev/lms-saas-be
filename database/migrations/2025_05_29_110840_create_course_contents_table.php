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
        Schema::create('course_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('course_contents')->onDelete('cascade');
            // add tenant_id if needed for multi-tenancy
             $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['module', 'chapter']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('position')->default(0);
            $table->integer('duration_mins')->nullable(); // overrides tenant default
            $table->timestamps();
            $table->index(['course_id', 'type', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_contents');
    }
};
