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
        Schema::table('courses', function (Blueprint $table) {
            // add status column to courses table with enum values created, published, archived and draft
            $table->enum('status', ['created', 'published', 'archived', 'draft'])
                ->default('created')
                ->after('is_active')
                ->comment('Status of the courses: created, published, archived, or draft');
        });
        Schema::table('courses', function (Blueprint $table) {
            // add index to status column
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // drop status column
            $table->dropColumn('status');
        });
    }
};
