#!/bin/bash

# Script to verify the database rebuild is working correctly

echo "===== VERIFYING DATABASE STRUCTURE AND SEED DATA ====="

# Check if tables exist
echo "Checking database tables..."
php artisan tinker --execute="DB::select('SHOW TABLES');"

# Check tenant count
echo "Verifying tenants..."
php artisan tinker --execute="App\\Models\\Tenant::count();"

# Check user count by role
echo "Verifying users by role..."
php artisan tinker --execute="App\\Models\\User::groupBy('role')->select('role', DB::raw('count(*) as total'))->get();"

# Check course structure
echo "Verifying course structure..."
php artisan tinker --execute="App\\Models\\Course::where('content_type', 'course')->with(['children' => function(\$query) { \$query->where('content_type', 'module'); }])->first();"

# Check course-user relationships
echo "Verifying course enrollments..."
php artisan tinker --execute="DB::table('course_user')->count();"

# Check student progress
echo "Verifying student progress..."
php artisan tinker --execute="App\\Models\\StudentProgress::count();"

echo "===== VERIFICATION COMPLETED ====="
