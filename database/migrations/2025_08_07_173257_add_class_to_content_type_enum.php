<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'class' to the content_type enum in courses table
        DB::statement("ALTER TABLE courses MODIFY COLUMN content_type ENUM('course', 'module', 'chapter', 'lesson', 'class') DEFAULT 'course'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'class' from the content_type enum
        DB::statement("ALTER TABLE courses MODIFY COLUMN content_type ENUM('course', 'module', 'chapter', 'lesson') DEFAULT 'course'");
    }
};
