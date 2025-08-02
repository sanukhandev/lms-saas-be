<?php

/**
 * Check Tenant Consistency Script
 * Verifies all models using BelongsToTenant trait have proper tenant_id columns in migrations
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== Tenant Consistency Check ===\n\n";

// Models that should have tenant_id
$modelsWithTenant = [
    'categories' => 'Category',
    'certificates' => 'Certificate',
    'class_sessions' => 'ClassSession',
    'courses' => 'Course',
    'course_contents' => 'CourseContent',
    'course_materials' => 'CourseMaterial',
    'course_purchases' => 'CoursePurchase',
    'exams' => 'Exam',
    'exam_questions' => 'ExamQuestion',
    'exam_results' => 'ExamResult',
    'feedback' => 'Feedback',
    'instructor_attendances' => 'InstructorAttendance',
    'instructor_payouts' => 'InstructorPayout',
    'invoices' => 'Invoice',
    'invoice_items' => 'InvoiceItem',
    'notifications' => 'Notification',
    'student_payments' => 'StudentPayment',
    'student_progress' => 'StudentProgress',
    'teaching_plans' => 'TeachingPlan',
    'tenant_settings' => 'TenantSetting',
    'payment_configs' => 'PaymentConfig',
];

$issues = [];
$fixed = [];

echo "Checking tables for tenant_id column...\n";

foreach ($modelsWithTenant as $table => $model) {
    if (Schema::hasTable($table)) {
        if (Schema::hasColumn($table, 'tenant_id')) {
            echo "  ✓ $table has tenant_id\n";
        } else {
            echo "  ✗ $table missing tenant_id\n";
            $issues[] = $table;
        }
    } else {
        echo "  ? $table does not exist\n";
    }
}

if (!empty($issues)) {
    echo "\n⚠ Found " . count($issues) . " tables missing tenant_id:\n";
    foreach ($issues as $table) {
        echo "  - $table\n";
    }

    echo "\nMigrations that may need fixing:\n";
    $migrationFiles = glob('database/migrations/*.php');

    foreach ($issues as $table) {
        foreach ($migrationFiles as $file) {
            if (strpos(basename($file), "create_{$table}_table") !== false) {
                echo "  - " . basename($file) . "\n";
            }
        }
    }
} else {
    echo "\n✓ All tables have proper tenant_id columns!\n";
}

echo "\nDone!\n";
