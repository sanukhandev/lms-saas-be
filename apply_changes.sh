#!/bin/bash

# Script to apply all the database rebuild changes

echo "===== APPLYING DATABASE REBUILD CHANGES ====="

# Apply the new README.md
echo "Updating README.md..."
mv README.md.new README.md

# Apply the new DatabaseSeeder.php
echo "Updating DatabaseSeeder.php..."
mv database/seeders/DatabaseSeeder.php.new database/seeders/DatabaseSeeder.php

# Make scripts executable
echo "Making scripts executable..."
chmod +x rebuild_database.sh
chmod +x verify_database.sh

echo "===== CHANGES APPLIED SUCCESSFULLY ====="
echo "Next steps:"
echo "1. Run 'bash rebuild_database.sh' to reset and rebuild the database"
echo "2. Run 'bash verify_database.sh' to verify the database structure"
echo "3. Test the API endpoints to ensure they work correctly"
