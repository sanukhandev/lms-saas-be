#!/bin/bash

# Script to verify the database rebuild is working correctly

echo "===== VERIFYING DATABASE STRUCTURE AND SEED DATA ====="

# Check all migration status
echo "Checking migration status..."
php artisan migrate:status

# Check tenant count
echo "Verifying tenants..."
php artisan tinker --execute="echo 'Tenant count: ' . App\\Models\\Tenant::count();"

# Check user count by role
echo "Verifying users by role..."
php artisan tinker --execute="print_r(App\\Models\\User::groupBy('role')->selectRaw('role, count(*) as count')->pluck('count', 'role')->toArray());"

# Check course structure
echo "Verifying course structure..."
php artisan tinker --execute="echo 'Total courses: ' . App\\Models\\Course::count();"
php artisan tinker --execute="echo 'Course hierarchical structure: ' . PHP_EOL; \$course = App\\Models\\Course::where('content_type', 'course')->with(['children' => function(\$q) { \$q->with('children'); }])->first(); echo \$course->title . ' (' . count(\$course->children) . ' modules)' . PHP_EOL; foreach(\$course->children as \$module) { echo '  - ' . \$module->title . ' (' . count(\$module->children) . ' chapters)' . PHP_EOL; }"

# Check course-user relationships
echo "Verifying course enrollments..."
php artisan tinker --execute="echo 'Course enrollments: ' . DB::table('course_user')->count();"

# Check student progress
echo "Verifying student progress..."
php artisan tinker --execute="echo 'Student progress records: ' . App\\Models\\StudentProgress::count();"

echo "===== VERIFICATION COMPLETED ====="
