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
        // List of duplicate migrations to mark as run
        $duplicateMigrations = [
            '2025_07_20_130742_create_tenants_table',
            '2025_07_20_130804_create_users_table',
            '2025_07_20_130827_create_categories_table',
            '2025_07_20_130847_create_courses_table',
            '2025_07_20_130958_create_course_user_table',
            '2025_07_20_130959_create_class_sessions_table',
            '2025_07_20_130959_create_course_contents_table',
            '2025_07_20_131000_create_session_user_table',
            '2025_07_20_131001_create_student_progress_table',
            '2025_07_20_131002_create_exams_table',
            '2025_07_20_131003_create_exam_questions_table',
            '2025_07_20_131004_create_exam_results_table',
            '2025_07_20_131005_create_invoices_table',
            '2025_07_20_131006_create_invoice_items_table',
            '2025_07_20_131007_create_instructor_payouts_table',
            '2025_07_20_131007_create_student_payments_table',
            '2025_07_20_131008_create_course_purchases_table',
            '2025_07_20_131009_create_notifications_table',
            '2025_07_20_131010_create_feedbacks_table',
            '2025_07_20_131010_create_tenant_settings_table',
            '2025_07_20_131011_create_payment_configs_table',
            '2025_07_20_131012_create_instructor_attendances_table',
            '2025_07_20_131013_create_course_materials_table',
            '2025_07_20_131013_create_teaching_plans_table',
            '2025_07_20_131936_add_foreign_key_to_class_sessions_content_id',
        ];

        // Get the latest batch number
        $latestBatch = DB::table('migrations')->max('batch');
        
        // Insert these migrations as if they've already run
        foreach ($duplicateMigrations as $migration) {
            // Check if the migration already exists in the migrations table
            $exists = DB::table('migrations')
                ->where('migration', $migration)
                ->exists();
                
            if (!$exists) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $latestBatch + 1
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // List of duplicate migrations to remove
        $duplicateMigrations = [
            '2025_07_20_130742_create_tenants_table',
            '2025_07_20_130804_create_users_table',
            '2025_07_20_130827_create_categories_table',
            '2025_07_20_130847_create_courses_table',
            '2025_07_20_130958_create_course_user_table',
            '2025_07_20_130959_create_class_sessions_table',
            '2025_07_20_130959_create_course_contents_table',
            '2025_07_20_131000_create_session_user_table',
            '2025_07_20_131001_create_student_progress_table',
            '2025_07_20_131002_create_exams_table',
            '2025_07_20_131003_create_exam_questions_table',
            '2025_07_20_131004_create_exam_results_table',
            '2025_07_20_131005_create_invoices_table',
            '2025_07_20_131006_create_invoice_items_table',
            '2025_07_20_131007_create_instructor_payouts_table',
            '2025_07_20_131007_create_student_payments_table',
            '2025_07_20_131008_create_course_purchases_table',
            '2025_07_20_131009_create_notifications_table',
            '2025_07_20_131010_create_feedbacks_table',
            '2025_07_20_131010_create_tenant_settings_table',
            '2025_07_20_131011_create_payment_configs_table',
            '2025_07_20_131012_create_instructor_attendances_table',
            '2025_07_20_131013_create_course_materials_table',
            '2025_07_20_131013_create_teaching_plans_table',
            '2025_07_20_131936_add_foreign_key_to_class_sessions_content_id',
        ];
        
        // Remove these migrations from the migrations table
        DB::table('migrations')
            ->whereIn('migration', $duplicateMigrations)
            ->delete();
    }
};
