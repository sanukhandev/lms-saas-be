#!/bin/bash

# Clean Duplicate Migrations Script
echo "=== Cleaning Duplicate Migration Files ==="

# List of duplicate migration files to remove (keeping the newer/better versions)
DUPLICATES=(
    "database/migrations/2025_07_20_130742_create_tenants_table.php"
    "database/migrations/2025_07_20_130804_create_users_table.php"
    "database/migrations/2025_07_20_130827_create_categories_table.php"
    "database/migrations/2025_07_20_130847_create_courses_table.php"
    "database/migrations/2025_07_20_130958_create_course_user_table.php"
    "database/migrations/2025_07_20_130959_create_class_sessions_table.php"
    "database/migrations/2025_07_20_130959_create_course_contents_table.php"
    "database/migrations/2025_07_20_131000_create_session_user_table.php"
    "database/migrations/2025_07_20_131001_create_student_progress_table.php"
    "database/migrations/2025_07_20_131002_create_exams_table.php"
    "database/migrations/2025_07_20_131003_create_exam_questions_table.php"
    "database/migrations/2025_07_20_131004_create_exam_results_table.php"
    "database/migrations/2025_07_20_131005_create_invoices_table.php"
    "database/migrations/2025_07_20_131006_create_invoice_items_table.php"
    "database/migrations/2025_07_20_131007_create_instructor_payouts_table.php"
    "database/migrations/2025_07_20_131007_create_student_payments_table.php"
    "database/migrations/2025_07_20_131008_create_course_purchases_table.php"
    "database/migrations/2025_07_20_131009_create_notifications_table.php"
    "database/migrations/2025_07_20_131010_create_feedbacks_table.php"
    "database/migrations/2025_07_20_131010_create_tenant_settings_table.php"
    "database/migrations/2025_07_20_131011_create_payment_configs_table.php"
    "database/migrations/2025_07_20_131012_create_instructor_attendances_table.php"
    "database/migrations/2025_07_20_131013_create_course_materials_table.php"
    "database/migrations/2025_07_20_131013_create_teaching_plans_table.php"
    "database/migrations/2025_07_20_131936_add_foreign_key_to_class_sessions_content_id.php"
    "database/migrations/2025_07_21_112820_add_release_date_to_course_contents_table.php"
    "database/migrations/2025_07_28_000001_create_modules_table.php"
    "database/migrations/2025_07_28_000002_add_tree_structure_to_courses_table.php"
    "database/migrations/2025_07_28_000003_drop_modules_and_chapters_tables.php"
    "database/migrations/2025_07_28_000004_cleanup_migrations.php"
    "database/migrations/2025_07_28_175858_mark_duplicate_migrations_as_run.php"
)

echo "Removing duplicate migration files..."

for file in "${DUPLICATES[@]}"; do
    if [ -f "$file" ]; then
        echo "  Removing: $(basename "$file")"
        rm "$file"
    else
        echo "  Not found: $(basename "$file")"
    fi
done

echo ""
echo "Remaining migration files:"
ls -la database/migrations/ | grep -E "\.(php)$" | awk '{print "  " $9}'

echo ""
echo "✓ Cleanup completed!"
echo ""
echo "Next steps:"
echo "  1. Run: php artisan migrate:fresh --seed"
echo "  2. Start server: php artisan serve"
echo "  3. Test login with admin@demo.com"
