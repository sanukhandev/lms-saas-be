#!/bin/bash

# Script to run the new migrations and seeders

echo "===== REBUILDING DATABASE WITH NEW MIGRATIONS AND SEEDERS ====="

# Reset the database
echo "Resetting database..."
php artisan migrate:reset --force

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Verify the new DatabaseSeeder.php file exists
if [ -f "database/seeders/DatabaseSeeder.php.new" ]; then
    echo "Replacing DatabaseSeeder.php with new version..."
    mv database/seeders/DatabaseSeeder.php.new database/seeders/DatabaseSeeder.php
else
    echo "New DatabaseSeeder.php file not found. Using existing version."
fi

# Run seeders
echo "Running database seeders..."
php artisan db:seed --force

echo "===== DATABASE REBUILD COMPLETED ====="
