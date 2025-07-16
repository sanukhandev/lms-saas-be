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
        // First, drop the foreign key constraint on student_id
        DB::statement('ALTER TABLE student_progress DROP FOREIGN KEY student_progress_student_id_foreign');

        // Then drop the unique constraint
        DB::statement('ALTER TABLE student_progress DROP INDEX student_progress_student_id_content_id_unique');

        // Then modify the table structure
        Schema::table('student_progress', function (Blueprint $table) {
            // Rename student_id to user_id for consistency
            $table->renameColumn('student_id', 'user_id');

            // Add new columns
            $table->decimal('completion_percentage', 5, 2)->default(0)->after('progress_percent');
            $table->integer('time_spent_mins')->default(0)->after('completion_percentage');
            $table->timestamp('last_accessed')->nullable()->after('time_spent_mins');

            // Make content_id nullable to allow course-level progress tracking
            $table->foreignId('content_id')->nullable()->change();
        });

        // Finally, add the new foreign key constraint and unique constraint
        Schema::table('student_progress', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'course_id', 'content_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new unique constraint and foreign key
        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'course_id', 'content_id']);
            $table->dropForeign(['user_id']);
        });

        // Revert the table structure
        Schema::table('student_progress', function (Blueprint $table) {
            // Rename back to student_id
            $table->renameColumn('user_id', 'student_id');

            // Remove new columns
            $table->dropColumn(['completion_percentage', 'time_spent_mins', 'last_accessed']);

            // Make content_id not nullable again
            $table->foreignId('content_id')->nullable(false)->change();
        });

        // Restore the original foreign key constraint and unique constraint
        Schema::table('student_progress', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['student_id', 'content_id']);
        });
    }
};
