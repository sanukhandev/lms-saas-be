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
        // Check if status column already exists (it should from the main courses migration)
        if (!Schema::hasColumn('courses', 'status')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->enum('status', ['created', 'published', 'archived', 'draft'])
                    ->default('created')
                    ->after('is_active')
                    ->comment('Status of the courses: created, published, archived, or draft');
            });
        }

        // Add index if it doesn't exist
        if (!Schema::hasIndex('courses', ['status'])) {
            Schema::table('courses', function (Blueprint $table) {
                $table->index('status');
            });
        }
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
