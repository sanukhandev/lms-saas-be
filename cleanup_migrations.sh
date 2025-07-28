#!/bin/bash

# Script to clean up duplicate migration files

echo "===== CLEANING UP DUPLICATE MIGRATION FILES ====="

# Create a backup directory
mkdir -p database/migrations_backup

# Move duplicates to backup
echo "Moving duplicate migrations to backup folder..."

# 1. Check for duplicate tenants table migrations
if [ -f "database/migrations/2025_07_20_130742_create_tenants_table.php" ] && [ -f "database/migrations/2025_07_28_000001_create_tenants_table.php" ]; then
    mv database/migrations/2025_07_20_130742_create_tenants_table.php database/migrations_backup/
    echo "Moved duplicate tenants table migration to backup"
fi

# 2. Check for duplicate class_sessions table migrations
if [ -f "database/migrations/2025_07_28_000006_create_class_sessions_table.php" ] && [ -f "database/migrations/2025_07_28_000007_create_class_sessions_table.php" ]; then
    mv database/migrations/2025_07_28_000006_create_class_sessions_table.php database/migrations_backup/
    echo "Moved duplicate class_sessions table migration to backup"
fi

# 3. Check for duplicate course_contents table migrations
if [ -f "database/migrations/2025_07_28_000005_create_course_contents_table.php" ] && [ -f "database/migrations/2025_07_28_000014_create_course_contents_table.php" ]; then
    mv database/migrations/2025_07_28_000014_create_course_contents_table.php database/migrations_backup/
    echo "Moved duplicate course_contents table migration to backup"
fi

echo "Duplicate migrations cleaned up. You can now run the rebuild_database.sh script."
